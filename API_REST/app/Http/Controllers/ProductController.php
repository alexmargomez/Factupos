<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['inventory', 'category', 'supplier'])->orderBy('id', 'desc')->get();
        $productWithStock = $products->map(function ($product) {
            $product->stock = $product->inventory ? $product->inventory->stock : 0;
            return $product;
        });

        return response()->json($productWithStock, 200);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'sku' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'cost_price' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'category_id' => 'nullable|exists:Categories,id',
            'supplier_id' => 'nullable|integer',
            'stock' => 'nullable|integer|min:0',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $product = Product::create([
                'sku' => $validated['sku'] ?? null,
                'name' => $validated['name'],
                'cost_price' => isset($validated['cost_price']) ? (int) $validated['cost_price'] : 0,
                'price' => (int) $validated['price'],
                'min_stock' => isset($validated['min_stock']) ? (int) $validated['min_stock'] : 5,
                'category_id' => $validated['category_id'] ?? null,
                'supplier_id' => !empty($validated['supplier_id']) ? (int) $validated['supplier_id'] : null,
            ]);

            if ($request->has('stock') && $validated['stock'] !== null) {
                $inventory = Inventory::updateOrCreate(
                    ['product_id' => $product->id],
                    ['stock' => (int) $validated['stock']]
                );

                if ((int) $validated['stock'] > 0) {
                    InventoryMovement::create([
                        'inventory_id' => $inventory->id,
                        'movement_type' => 'incoming',
                        'quantity' => (int) $validated['stock'],
                        'movement_date' => now(),
                        'reason' => 'Stock inicial de creación',
                    ]);
                }
                $product->stock = $inventory->stock;
            } else {
                $product->stock = 0;
            }

            $product->load(['inventory', 'supplier']);
            return response()->json($product, 201);
        });
    }

    public function show($id)
    {
        $product = Product::with(['inventory', 'supplier'])->findOrFail($id);
        $product->stock = $product->inventory ? $product->inventory->stock : 0;
        return response()->json($product, 200);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'sku' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'cost_price' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'category_id' => 'nullable|exists:Categories,id',
            'supplier_id' => 'nullable|integer',
            'stock' => 'nullable|integer|min:0',
        ]);

        return DB::transaction(function () use ($validated, $request, $id) {
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

            if ($request->has('stock') && $validated['stock'] !== null) {
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
            $product->stock = $product->inventory ? $product->inventory->stock : 0;

            return response()->json($product, 200);
        });
    }

    public function registerPurchase(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'cost_price' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|integer',
            'invoice_ref' => 'nullable|string|max:120',
        ]);

        return DB::transaction(function () use ($validated, $id) {
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
            $product->stock = $product->inventory ? $product->inventory->stock : 0;

            return response()->json($product, 200);
        });
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
    
        return response()->json(['message' => 'Producto eliminado exitosamente.'], 200);
    }
}