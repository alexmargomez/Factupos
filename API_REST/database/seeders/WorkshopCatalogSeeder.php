<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Schedule;
use App\Models\Supplier;
use App\Models\Vehicle;
use App\Models\Worker;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeder de catálogo maestro de repuestos automotrices/motocicletas,
 * proveedores, mecánicos con porcentaje de comisión y órdenes de taller.
 * Puede ejecutarse de forma idempotente mediante:
 * php artisan db:seed --class=WorkshopCatalogSeeder
 */
class WorkshopCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Categorías especializadas del taller y almacén de repuestos
        $categoriesData = [
            'Lubricantes y Fluidos',
            'Filtros y Afinación',
            'Sistema de Frenos',
            'Suspensión y Dirección',
            'Kit de Arrastre y Transmisión',
            'Sistema Eléctrico y Baterías',
            'Llantas y Neumáticos',
            'Accesorios y Consumibles',
        ];

        $categories = [];
        foreach ($categoriesData as $catName) {
            $categories[$catName] = Category::firstOrCreate(['name' => $catName]);
        }

        // 2. Directorio de proveedores mayoristas
        $suppliersData = [
            [
                'name' => 'Distribuidora Nacional de Repuestos S.A.S.',
                'nit' => '900.412.889-1',
                'contact_name' => 'Carlos Andrés Mejía',
                'phone' => '3104567890',
                'email' => 'ventas@dinarepuestos.com.co',
                'address' => 'Zona Industrial Calle 14 # 52-18',
                'notes' => 'Proveedor principal de frenos, kits de arrastre y rodamientos.',
            ],
            [
                'name' => 'Lubricantes y Filtros del Valle Ltda.',
                'nit' => '890.305.114-7',
                'contact_name' => 'Diana Marcela Gómez',
                'phone' => '3157894321',
                'email' => 'pedidos@lubrivalle.co',
                'address' => 'Av. Principal # 28-90 Bodega 4',
                'notes' => 'Distribuidor autorizado Motul, Mobil, Castrol y filtros OEM.',
            ],
            [
                'name' => 'Importadora ElectroPartes Colombia',
                'nit' => '901.228.540-3',
                'contact_name' => 'Jorge Eliécer Restrepo',
                'phone' => '3006549870',
                'email' => 'comercial@electropartescol.com',
                'address' => 'Carrera 15 # 19-44 Local 102',
                'notes' => 'Baterías, bujías iridium, reguladores y bombillería LED.',
            ],
        ];

        $suppliers = [];
        foreach ($suppliersData as $sup) {
            $suppliers[$sup['name']] = Supplier::firstOrCreate(
                ['name' => $sup['name']],
                $sup
            );
        }

        // 3. Técnicos / Mecánicos con esquema de porcentaje de comisión
        $workersData = [
            ['name' => 'Mauricio Hernández (Jefe de Taller)', 'commission_percentage' => 60],
            ['name' => 'Sebastián Ríos (Especialista Frenos y Suspensión)', 'commission_percentage' => 50],
            ['name' => 'Andrés Felipe Salazar (Electricidad e Inyección)', 'commission_percentage' => 55],
        ];

        foreach ($workersData as $wData) {
            Worker::firstOrCreate(
                ['name' => $wData['name']],
                [
                    'totalDia' => 0,
                    'commission_percentage' => $wData['commission_percentage'],
                ]
            );
        }

        // 4. Catálogo valorizado de productos con SKU, Costo, Precio de Venta y Stock Mínimo
        $catalogProducts = [
            [
                'sku' => 'LUB-MOT-20W50',
                'name' => 'Aceite Motul 3000 20W50 4T Mineral (Cuarto 1L)',
                'cost_price' => 31000,
                'price' => 45000,
                'min_stock' => 12,
                'stock' => 28,
                'category' => 'Lubricantes y Fluidos',
                'supplier' => 'Lubricantes y Filtros del Valle Ltda.',
            ],
            [
                'sku' => 'LUB-MOT-10W40',
                'name' => 'Aceite Motul 7100 10W40 100% Sintético (1L)',
                'cost_price' => 54000,
                'price' => 78000,
                'min_stock' => 8,
                'stock' => 16,
                'category' => 'Lubricantes y Fluidos',
                'supplier' => 'Lubricantes y Filtros del Valle Ltda.',
            ],
            [
                'sku' => 'LUB-MOB-5W30',
                'name' => 'Aceite Mobil Super 3000 5W30 Sintético Galón (4L)',
                'cost_price' => 145000,
                'price' => 198000,
                'min_stock' => 5,
                'stock' => 9,
                'category' => 'Lubricantes y Fluidos',
                'supplier' => 'Lubricantes y Filtros del Valle Ltda.',
            ],
            [
                'sku' => 'FIL-ACE-HF138',
                'name' => 'Filtro de Aceite Hiflofiltro HF138 Alta Eficiencia',
                'cost_price' => 16500,
                'price' => 28000,
                'min_stock' => 10,
                'stock' => 22,
                'category' => 'Filtros y Afinación',
                'supplier' => 'Lubricantes y Filtros del Valle Ltda.',
            ],
            [
                'sku' => 'FRE-PAS-DEL01',
                'name' => 'Juego de Pastillas de Freno Delanteras Cerámicas',
                'cost_price' => 38000,
                'price' => 62000,
                'min_stock' => 6,
                'stock' => 14,
                'category' => 'Sistema de Frenos',
                'supplier' => 'Distribuidora Nacional de Repuestos S.A.S.',
            ],
            [
                'sku' => 'FRE-LIQ-DOT4',
                'name' => 'Líquido de Frenos Sintético DOT 4 (Frasco 500ml)',
                'cost_price' => 14000,
                'price' => 24000,
                'min_stock' => 8,
                'stock' => 18,
                'category' => 'Sistema de Frenos',
                'supplier' => 'Lubricantes y Filtros del Valle Ltda.',
            ],
            [
                'sku' => 'TRA-KIT-428H',
                'name' => 'Kit de Arrastre Reforzado Cadena Oring 428H',
                'cost_price' => 92000,
                'price' => 145000,
                'min_stock' => 4,
                'stock' => 7,
                'category' => 'Kit de Arrastre y Transmisión',
                'supplier' => 'Distribuidora Nacional de Repuestos S.A.S.',
            ],
            [
                'sku' => 'ELE-BAT-YTX7A',
                'name' => 'Batería Libre Mantenimiento Gel YTX7A-BS 12V',
                'cost_price' => 88000,
                'price' => 135000,
                'min_stock' => 4,
                'stock' => 6,
                'category' => 'Sistema Eléctrico y Baterías',
                'supplier' => 'Importadora ElectroPartes Colombia',
            ],
            [
                'sku' => 'ELE-BUJ-NGK9',
                'name' => 'Bujía NGK Iridium IX Alto Desempeño',
                'cost_price' => 29000,
                'price' => 48000,
                'min_stock' => 10,
                'stock' => 20,
                'category' => 'Sistema Eléctrico y Baterías',
                'supplier' => 'Importadora ElectroPartes Colombia',
            ],
        ];

        foreach ($catalogProducts as $item) {
            $category = $categories[$item['category']] ?? null;
            $supplier = $suppliers[$item['supplier']] ?? null;

            $product = Product::firstOrCreate(
                ['sku' => $item['sku']],
                [
                    'name' => $item['name'],
                    'cost_price' => $item['cost_price'],
                    'price' => $item['price'],
                    'min_stock' => $item['min_stock'],
                    'category_id' => $category?->id,
                    'supplier_id' => $supplier?->id,
                ]
            );

            $inventory = Inventory::firstOrCreate(
                ['product_id' => $product->id],
                ['quantity' => $item['stock']]
            );

            if ($inventory->wasRecentlyCreated && $item['stock'] > 0) {
                InventoryMovement::create([
                    'inventory_id' => $inventory->id,
                    'movement_type' => 'incoming',
                    'quantity' => $item['stock'],
                    'reason' => 'Inventario inicial de catálogo semilla',
                    'movement_date' => Carbon::now(),
                ]);
            }
        }
    }
}
