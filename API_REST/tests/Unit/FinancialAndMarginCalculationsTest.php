<?php

namespace Tests\Unit;

use App\Services\WorkshopKanbanService;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas unitarias de utilidades de normalización y reglas financieras de FactuPOS.
 */
class FinancialAndMarginCalculationsTest extends TestCase
{
    public function test_workshop_kanban_service_normalizes_json_and_array_service_lists(): void
    {
        $service = new WorkshopKanbanService();

        $fromJson = $service->normalizeServicesList('["Cambio de aceite","Ajuste de frenos"]');
        $this->assertSame(['Cambio de aceite', 'Ajuste de frenos'], $fromJson);

        $fromArray = $service->normalizeServicesList(['Sincronización', 'Escáner OBD2']);
        $this->assertSame(['Sincronización', 'Escáner OBD2'], $fromArray);

        $fromEmpty = $service->normalizeServicesList('');
        $this->assertSame([], $fromEmpty);
    }

    public function test_kanban_stages_constant_contains_all_five_operational_columns(): void
    {
        $this->assertCount(5, WorkshopKanbanService::KANBAN_STAGES);
        $this->assertContains('Recibido', WorkshopKanbanService::KANBAN_STAGES);
        $this->assertContains('En Diagnóstico', WorkshopKanbanService::KANBAN_STAGES);
        $this->assertContains('Esperando Repuesto', WorkshopKanbanService::KANBAN_STAGES);
        $this->assertContains('En Reparación', WorkshopKanbanService::KANBAN_STAGES);
        $this->assertContains('Listo para Entrega', WorkshopKanbanService::KANBAN_STAGES);
    }
}
