<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pruebas integrales para la gestión de proveedores, catálogo de productos con SKU,
 * inventario valorizado (costo de compra vs. precio de venta) y actualización de costos
 * en entradas de compras.
 */
class InventoryValuationAndSuppliersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create([
            'username' => 'admin_inventory_test',
            'role' => 'Admin',
        ]));
    }

    public function test_can_create_supplier_and_associate_valued_product_with_sku(): void
    {
        $supplierResponse = $this->postJson('/api/suppliers', [
            'name' => 'Importadora de Lubricantes Andina S.A.S.',
            'nit' => '901.555.888-2',
            'contact_name' => 'Fernando Ospina',
            'phone' => '3129998877',
            'email' => 'ventas@lubriandina.co',
            'address' => 'Parque Industrial Bodega 19',
        ]);

        $supplierResponse->assertStatus(201)
            ->assertJsonPath('name', 'Importadora de Lubricantes Andina S.A.S.');

        $supplierId = $supplierResponse->json('id');

        $productResponse = $this->postJson('/api/products', [
            'sku' => 'MOT-7100-10W40',
            'name' => 'Aceite Sintético 4T 10W40',
            'cost_price' => 48000,
            'price' => 72000,
            'min_stock' => 6,
            'stock' => 25,
            'supplier_id' => $supplierId,
        ]);

        $productResponse->assertStatus(201)
            ->assertJsonPath('sku', 'MOT-7100-10W40')
            ->assertJsonPath('cost_price', 48000)
            ->assertJsonPath('price', 72000)
            ->assertJsonPath('min_stock', 6)
            ->assertJsonPath('stock', 25)
            ->assertJsonPath('supplier_id', $supplierId);

        $this->assertDatabaseHas('Inventory', [
            'product_id' => $productResponse->json('id'),
            'quantity' => 25,
        ]);
    }

    public function test_incoming_inventory_movement_updates_stock_and_product_unit_cost(): void
    {
        $supplier = Supplier::create([
            'name' => 'Autopartes del Pacífico',
            'nit' => '900.123.456-7',
            'phone' => '3187776655',
        ]);

        $productResponse = $this->postJson('/api/products', [
            'sku' => 'BAT-GEL-12V',
            'name' => 'Batería Gel 12V 7Ah',
            'cost_price' => 80000,
            'price' => 125000,
            'min_stock' => 4,
            'stock' => 5,
            'supplier_id' => $supplier->id,
        ]);

        $productId = $productResponse->json('id');

        // Registrar compra de 10 unidades con nuevo costo unitario de $85.000
        $movementResponse = $this->postJson('/api/inventory-movements', [
            'product_id' => $productId,
            'movement_type' => 'incoming',
            'quantity' => 10,
            'unit_cost' => 85000,
            'reason' => 'Compra Factura #FP-9021 (Proveedor: Autopartes del Pacífico)',
        ]);

        $movementResponse->assertStatus(201);

        $this->assertDatabaseHas('Inventory', [
            'product_id' => $productId,
            'quantity' => 15,
        ]);

        $this->assertDatabaseHas('Products', [
            'id' => $productId,
            'cost_price' => 85000,
        ]);
    }

    public function test_analytics_inventory_valuation_endpoint_calculates_capital_and_projected_profit(): void
    {
        $this->postJson('/api/products', [
            'sku' => 'KIT-ARR-001',
            'name' => 'Kit de Arrastre Acero 1045',
            'cost_price' => 60000,
            'price' => 100000,
            'min_stock' => 5,
            'stock' => 10,
        ])->assertStatus(201);

        $valuationResponse = $this->getJson('/api/analytics/inventory-valuation');

        $valuationResponse->assertStatus(200)
            ->assertJsonPath('summary.total_skus', 1)
            ->assertJsonPath('summary.total_units_in_stock', 10)
            ->assertJsonPath('summary.total_capital_at_cost', 600000)
            ->assertJsonPath('summary.total_potential_revenue', 1000000)
            ->assertJsonPath('summary.total_projected_profit', 400000);
    }
}
