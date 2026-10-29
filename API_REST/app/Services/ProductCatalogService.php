<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de Dominio y Aplicación para la gestión del catálogo de productos,
 * sincronización transaccional de existencias en bodega y registro en Kardex.
 */
class ProductCatalogService
{
    public function listAllWithStock(): Collection
    {
        $products = Product::with(['inventory', 'category', 'supplier'])
            ->orderBy('id', 'desc')
            ->get();

        return $products->map(function (Product $product) {
            $product->stock = $product->inventory ? (int) $product->inventory->stock : 0;
            return $product;
        });
    }

    public function findWithStockOrFail(int|string $id): Product
    {
        $product = Product::with(['inventory', 'supplier'])->findOrFail($id);
        $product->stock = $product->inventory ? (int) $product->inventory->stock : 0;
        return $product;
    }

    public function createProductWithInitialStock(array $validated, bool $hasStockInput): Product
    {
        return DB::transaction(function () use ($validated, $hasStockInput) {
            $product = Product::create([
                'sku' => $validated['sku'] ?? null,
                'name' => $validated['name'],
                'cost_price' => isset($validated['cost_price']) ? (int) $validated['cost_price'] : 0,
                'price' => (int) $validated['price'],
                'min_stock' => isset($validated['min_stock']) ? (int) $validated['min_stock'] : 5,
                'category_id' => $validated['category_id'] ?? null,
                'supplier_id' => !empty($validated['supplier_id']) ? (int) $validated['supplier_id'] : null,
            ]);

            if ($hasStockInput && array_key_exists('stock', $validated) && $validated['stock'] !== null) {
                $initialStock = (int) $validated['stock'];
                $inventory = Inventory::updateOrCreate(
                    ['product_id' => $product->id],
                    ['stock' => $initialStock]
                );

                if ($initialStock > 0) {
                    InventoryMovement::create([
                        'inventory_id' => $inventory->id,
                        'movement_type' => 'incoming',
                        'quantity' => $initialStock,
                        'movement_date' => now(),
                        'reason' => 'Stock inicial de creación',
                    ]);
                }
                $product->stock = (int) $inventory->stock;
            } else {
                $product->stock = 0;
            }

            $product->load(['inventory', 'supplier']);
            return $product;
        });
    }

    public function updateProductAndSyncKardex(int|string $id, array $validated, bool $hasStockInput): Product
    {
        return DB::transaction(function () use ($id, $validated, $hasStockInput) {
            $product = Product::findOrFail($id);
            $product->update([
                'sku' => array_key_exists('sku', $validated) ? $validated['sku'] : $product->sku,
                'name' => $validated['name'],
                'cost_price' => array_key_exists('cost_price', $validated) && $validated['cost_price'] !== null
                    ? (int) $validated['cost_price']
                    : $product->cost_price,
                'price' => (int) $validated['price'],
                'min_stock' => array_key_exists('min_stock', $validated) && $validated['min_stock'] !== null
                    ? (int) $validated['min_stock']
                    : $product->min_stock,
                'category_id' => $validated['category_id'] ?? $product->category_id,
                'supplier_id' => array_key_exists('supplier_id', $validated)
                    ? (!empty($validated['supplier_id']) ? (int) $validated['supplier_id'] : null)
                    : $product->supplier_id,
            ]);

            if ($hasStockInput && array_key_exists('stock', $validated) && $validated['stock'] !== null) {
                $inventory = Inventory::firstOrCreate(
                    ['product_id' => $product->id],
                    ['stock' => 0]
                );
                $diff = (int) $validated['stock'] - (int) $inventory->stock;
                $inventory->stock = (int) $validated['stock'];
                $inventory->save();

                if ($diff !== 0) {
                    InventoryMovement::create([
                        'inventory_id' => $inventory->id,
                        'movement_type' => $diff > 0 ? 'incoming' : 'outgoing',
                        'quantity' => abs($diff),
                        'movement_date' => now(),
                        'reason' => 'Actualización desde edición de producto',
                    ]);
                }
            }

            $product->load(['inventory', 'supplier']);
            $product->stock = $product->inventory ? (int) $product->inventory->stock : 0;

            return $product;
        });
    }

    public function registerMerchandisePurchase(int|string $id, array $validated): Product
    {
        return DB::transaction(function () use ($id, $validated) {
            $product = Product::findOrFail($id);

            if (isset($validated['cost_price']) && $validated['cost_price'] !== null) {
                $product->cost_price = (int) $validated['cost_price'];
            }
            if (isset($validated['price']) && $validated['price'] !== null) {
                $product->price = (int) $validated['price'];
            }
            if (array_key_exists('supplier_id', $validated) && !empty($validated['supplier_id'])) {
                $product->supplier_id = (int) $validated['supplier_id'];
            }
            $product->save();

            $inventory = Inventory::firstOrCreate(
                ['product_id' => $product->id],
                ['stock' => 0]
            );
            $inventory->stock = ((int) $inventory->stock) + (int) $validated['quantity'];
            $inventory->save();

            $reason = 'Compra de mercancía';
            if (!empty($validated['invoice_ref'])) {
                $reason .= ' (Ref: ' . $validated['invoice_ref'] . ')';
            }

            InventoryMovement::create([
                'inventory_id' => $inventory->id,
                'movement_type' => 'incoming',
                'quantity' => (int) $validated['quantity'],
                'movement_date' => now(),
                'reason' => $reason,
            ]);

            $product->load(['inventory', 'supplier']);
            $product->stock = $product->inventory ? (int) $product->inventory->stock : 0;

            return $product;
        });
    }
}
