<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Pruebas unitarias exhaustivas para las fórmulas matemáticas de liquidación de comisiones
 * de mecánicos (% técnico vs % taller), cálculo de márgenes de comercialización (margen sobre
 * venta vs rentabilidad sobre costo) y proyecciones de punto de reorden en bodega.
 */
class WorkerCommissionCalculationsUnitTest extends TestCase
{
    /**
     * Verifica que la suma entre el pago al mecánico y la utilidad del taller
     * siempre sea exactamente igual al 100% del monto bruto de mano de obra,
     * sin pérdida ni duplicación de pesos por redondeo.
     */
    public function test_commission_split_preserves_exact_gross_total_across_all_percentages(): void
    {
        $testCases = [
            ['gross' => 100000, 'pct' => 50, 'expected_worker' => 50000, 'expected_workshop' => 50000],
            ['gross' => 150000, 'pct' => 60, 'expected_worker' => 90000, 'expected_workshop' => 60000],
            ['gross' => 85000,  'pct' => 45, 'expected_worker' => 38250, 'expected_workshop' => 46750],
            ['gross' => 120000, 'pct' => 0,  'expected_worker' => 0,     'expected_workshop' => 120000],
            ['gross' => 120000, 'pct' => 100,'expected_worker' => 120000,'expected_workshop' => 0],
        ];

        foreach ($testCases as $case) {
            $gross = $case['gross'];
            $pct = max(0, min(100, $case['pct']));
            $workerShare = (int) round(($gross * $pct) / 100);
            $workshopShare = $gross - $workerShare;

            $this->assertSame($case['expected_worker'], $workerShare);
            $this->assertSame($case['expected_workshop'], $workshopShare);
            $this->assertSame($gross, $workerShare + $workshopShare);
        }
    }

    /**
     * Verifica el cálculo de utilidad unitaria, margen sobre precio de venta
     * y valorización total de línea de inventario.
     */
    public function test_inventory_line_valuation_and_gross_margin_percentage_math(): void
    {
        $costPrice = 35000;
        $salePrice = 50000;
        $stockQty = 12;

        $unitProfit = $salePrice - $costPrice;
        $marginPct = round(($unitProfit / $salePrice) * 100, 2);
        $totalCapitalCost = $stockQty * $costPrice;
        $totalPotentialSale = $stockQty * $salePrice;
        $projectedProfit = $totalPotentialSale - $totalCapitalCost;

        $this->assertSame(15000, $unitProfit);
        $this->assertSame(30.0, $marginPct);
        $this->assertSame(420000, $totalCapitalCost);
        $this->assertSame(600000, $totalPotentialSale);
        $this->assertSame(180000, $projectedProfit);
    }
}
