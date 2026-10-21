<?php

namespace App\Services;

use App\Models\Worker;
use App\Models\WorkerSettlement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de dominio para el cálculo de comisiones de técnicos/mecánicos,
 * ejecución transaccional de liquidaciones y generación de reportes de nómina técnica.
 */
class WorkerCommissionService
{
    /**
     * Calcula el estado actual de comisiones pendientes de liquidar para todos los mecánicos.
     *
     * @return array<string, mixed>
     */
    public function getPendingCommissionsOverview(): array
    {
        $workers = Worker::orderBy('name')->get();

        $totalGrossLaborDay = 0;
        $totalMechanicPayoutPending = 0;
        $totalWorkshopSharePending = 0;

        $workerRows = [];

        foreach ($workers as $worker) {
            $grossAmount = max(0, (int) ($worker->totalDia ?? 0));
            $pct = max(0, min(100, (int) ($worker->commission_percentage ?? 50)));
            $mechanicPayout = (int) round(($grossAmount * $pct) / 100);
            $workshopShare = $grossAmount - $mechanicPayout;

            $totalGrossLaborDay += $grossAmount;
            $totalMechanicPayoutPending += $mechanicPayout;
            $totalWorkshopSharePending += $workshopShare;

            $workerRows[] = [
                'worker_id' => $worker->id,
                'name' => $worker->name,
                'commission_percentage' => $pct,
                'workshop_percentage' => 100 - $pct,
                'gross_daily_labor' => $grossAmount,
                'pending_mechanic_payout' => $mechanicPayout,
                'pending_workshop_share' => $workshopShare,
                'has_pending_settlement' => $grossAmount > 0,
            ];
        }

        return [
            'workers_count' => $workers->count(),
            'total_gross_daily_labor' => $totalGrossLaborDay,
            'total_pending_mechanic_payout' => $totalMechanicPayoutPending,
            'total_pending_workshop_share' => $totalWorkshopSharePending,
            'workers' => $workerRows,
        ];
    }

    /**
     * Ejecuta la liquidación transaccional de un mecánico, registrando el historial
     * auditable en WorkerSettlements y reiniciando su acumulado diario a cero.
     *
     * @param Worker      $worker
     * @param string|null $notes
     * @return WorkerSettlement
     */
    public function settleWorkerBalance(Worker $worker, ?string $notes = null): WorkerSettlement
    {
        return DB::transaction(function () use ($worker, $notes) {
            $grossAmount = max(0, (int) ($worker->totalDia ?? 0));
            $pct = max(0, min(100, (int) ($worker->commission_percentage ?? 50)));
            $commissionAmount = (int) round(($grossAmount * $pct) / 100);
            $workshopAmount = $grossAmount - $commissionAmount;

            $settlement = WorkerSettlement::create([
                'worker_id' => $worker->id,
                'gross_amount' => $grossAmount,
                'commission_percentage' => $pct,
                'commission_amount' => $commissionAmount,
                'workshop_amount' => $workshopAmount,
                'settled_at' => Carbon::now(),
                'notes' => $notes ?: sprintf('Liquidación de turno (%d%% técnico / %d%% taller)', $pct, 100 - $pct),
            ]);

            $worker->totalDia = 0;
            $worker->save();

            return $settlement->load('worker');
        });
    }

    /**
     * Obtiene el historial consolidado de liquidaciones pagadas en un rango de fechas.
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array<string, mixed>
     */
    public function getSettlementsHistoryReport(?string $startDate = null, ?string $endDate = null): array
    {
        $query = WorkerSettlement::with('worker')->orderByDesc('settled_at');

        if ($startDate && $endDate) {
            $query->whereBetween('settled_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        }

        $settlements = $query->get();

        return [
            'settlements_count' => $settlements->count(),
            'total_gross_settled' => (int) $settlements->sum('gross_amount'),
            'total_paid_to_mechanics' => (int) $settlements->sum('commission_amount'),
            'total_retained_by_workshop' => (int) $settlements->sum('workshop_amount'),
            'by_worker' => $settlements->groupBy('worker_id')->map(function ($items) {
                $first = $items->first();
                return [
                    'worker_id' => $first->worker_id,
                    'worker_name' => $first->worker->name ?? sprintf('Técnico #%d', $first->worker_id),
                    'settlements_count' => $items->count(),
                    'gross_total' => (int) $items->sum('gross_amount'),
                    'commission_paid_total' => (int) $items->sum('commission_amount'),
                    'workshop_retained_total' => (int) $items->sum('workshop_amount'),
                ];
            })->values()->all(),
            'records' => $settlements->toArray(),
        ];
    }
}
