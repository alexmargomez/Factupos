<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Vehicle;
use App\Models\Worker;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Servicio de dominio para la facturación en Punto de Venta (POS),
 * validación de esquemas de pago (Efectivo, Transferencia, Mixto y Crédito),
 * aplicación de abonos de cartera y generación de resúmenes para WhatsApp.
 */
class PosBillingService
{
    /**
     * Métodos de pago soportados oficialmente por la caja POS.
     *
     * @var array<int, string>
     */
    public const SUPPORTED_PAYMENT_METHODS = [
        'Efectivo',
        'Transferencia / Nequi',
        'Tarjeta',
        'Pago Mixto',
        'Crédito / Pendiente',
    ];

    /**
     * Calcula y normaliza el desglose de recaudo, saldo pendiente y estado de pago
     * para una factura o transacción de venta POS.
     *
     * @param int         $totalAmount   Monto total de la factura en pesos ($ COP)
     * @param string|null $paymentMethod Método de pago seleccionado
     * @param int|null    $paidAmount    Monto efectivamente recibido o abonado
     * @param string|null $paymentNotes  Notas del desglose (ej. distribución en pago mixto)
     * @return array{
     *     total: int,
     *     payment_method: string,
     *     payment_status: string,
     *     paid_amount: int,
     *     pending_balance: int,
     *     payment_notes: string|null
     * }
     */
    public function normalizePaymentBreakdown(
        int $totalAmount,
        ?string $paymentMethod = 'Efectivo',
        ?int $paidAmount = null,
        ?string $paymentNotes = null
    ): array {
        if ($totalAmount < 0) {
            throw new InvalidArgumentException('El monto total de la factura no puede ser negativo.');
        }

        $method = trim((string) $paymentMethod) !== '' ? trim((string) $paymentMethod) : 'Efectivo';

        if ($method === 'Crédito / Pendiente') {
            $normalizedPaid = $paidAmount !== null ? max(0, min($totalAmount, $paidAmount)) : 0;
        } else {
            $normalizedPaid = $paidAmount !== null ? max(0, min($totalAmount, $paidAmount)) : $totalAmount;
        }

        $pendingBalance = max(0, $totalAmount - $normalizedPaid);

        if ($pendingBalance === 0) {
            $status = 'Pagado';
        } elseif ($normalizedPaid > 0) {
            $status = 'Parcial';
        } else {
            $status = 'Pendiente';
        }

        return [
            'total' => $totalAmount,
            'payment_method' => $method,
            'payment_status' => $status,
            'paid_amount' => $normalizedPaid,
            'pending_balance' => $pendingBalance,
            'payment_notes' => $paymentNotes ? trim($paymentNotes) : null,
        ];
    }

    /**
     * Aplica un abono monetario sobre una venta con saldo pendiente en cartera
     * dentro de una transacción atómica.
     *
     * @param Sale        $sale
     * @param int         $abonoAmount
     * @param string|null $notes
     * @return Sale
     */
    public function applyReceivableAbono(Sale $sale, int $abonoAmount, ?string $notes = null): Sale
    {
        if ($abonoAmount <= 0) {
            throw new InvalidArgumentException('El valor del abono debe ser mayor a $0 COP.');
        }

        return DB::transaction(function () use ($sale, $abonoAmount, $notes) {
            $total = max(0, (int) $sale->total);
            $currentPaid = max(0, (int) ($sale->paid_amount ?? 0));
            $newPaid = min($total, $currentPaid + $abonoAmount);
            $newPending = max(0, $total - $newPaid);

            $sale->paid_amount = $newPaid;
            $sale->pending_balance = $newPending;
            $sale->payment_status = $newPending === 0
                ? 'Pagado'
                : ($newPaid > 0 ? 'Parcial' : 'Pendiente');

            if ($notes !== null && trim($notes) !== '') {
                $sale->payment_notes = trim($notes);
            }

            $sale->save();

            return $sale->fresh(['customer', 'vehicle', 'worker', 'invoice']);
        });
    }

    /**
     * Construye el texto estructurado de comprobante de factura o cotización
     * listo para enviar al cliente vía enlace profundo de WhatsApp (wa.me).
     *
     * @param Sale $sale
     * @param bool $isQuotation
     * @return array{phone_formatted: string|null, message: string, whatsapp_url: string|null}
     */
    public function buildWhatsAppInvoicePayload(Sale $sale, bool $isQuotation = false): array
    {
        $sale->loadMissing(['customer', 'vehicle', 'worker', 'invoice', 'saleDetails.product']);
        $services = Service::where('sale_id', $sale->id)->get();

        $customerName = $sale->customer->name ?? 'Cliente';
        $rawPhone = preg_replace('/\D+/', '', (string) ($sale->customer->phone ?? ''));
        $formattedPhone = null;

        if ($rawPhone !== '') {
            $formattedPhone = strlen($rawPhone) === 10 ? '57' . $rawPhone : $rawPhone;
        }

        $docTitle = $isQuotation ? 'COTIZACIÓN DE SERVICIO Y REPUESTOS' : 'COMPROBANTE DE FACTURA POS';
        $docNumber = $sale->invoice->invoice_number ?? sprintf('FACT-%06d', $sale->id);

        $lines = [
            "Hola *{$customerName}*, te enviamos tu *{$docTitle}* de *FactuPOS Taller*:",
            "",
            "🧾 *Documento:* {$docNumber}",
            "📅 *Fecha:* " . ($sale->created_at ? $sale->created_at->format('Y-m-d H:i') : Carbon::now()->format('Y-m-d H:i')),
        ];

        if ($sale->vehicle) {
            $lines[] = "🏍️ *Vehículo:* {$sale->vehicle->plate} ({$sale->vehicle->make} {$sale->vehicle->model})";
        }

        if ($sale->saleDetails && $sale->saleDetails->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "📦 *Repuestos e Insumos:*";
            foreach ($sale->saleDetails as $detail) {
                $pName = $detail->product->name ?? "Producto #{$detail->product_id}";
                $sub = number_format((int) $detail->subtotal, 0, ',', '.');
                $lines[] = "• {$detail->quantity}x {$pName}: $ {$sub}";
            }
        }

        if ($services->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "🔧 *Mano de Obra y Servicios:*";
            foreach ($services as $srv) {
                $sPrice = number_format((int) $srv->price_service, 0, ',', '.');
                $lines[] = "• {$srv->service}: $ {$sPrice}";
            }
        }

        $totalFormatted = number_format((int) $sale->total, 0, ',', '.');
        $lines[] = "";
        $lines[] = "💰 *TOTAL:* $ {$totalFormatted}";
        $lines[] = "💳 *Método de Pago:* " . ($sale->payment_method ?: 'Efectivo');

        if ((int) ($sale->pending_balance ?? 0) > 0) {
            $pendingFormatted = number_format((int) $sale->pending_balance, 0, ',', '.');
            $lines[] = "⚠️ *Saldo Pendiente por Pagar:* $ {$pendingFormatted}";
        }

        $lines[] = "";
        $lines[] = "¡Gracias por confiar el mantenimiento de tu vehículo a nuestro taller!";

        $message = implode("\n", $lines);
        $whatsappUrl = $formattedPhone
            ? 'https://wa.me/' . $formattedPhone . '?text=' . rawurlencode($message)
            : null;

        return [
            'phone_formatted' => $formattedPhone,
            'message' => $message,
            'whatsapp_url' => $whatsappUrl,
        ];
    }
}
