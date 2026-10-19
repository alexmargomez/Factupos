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
        $products = Product::with(['inventory', 'category'])->orderBy('id', 'desc')->get();
        $productWithStock = $products->map(function ($product) {
            $product->stock = $product->inventory ? $product->inventory->stock : 0;
            return $product;
        });

        return response()->json($productWithStock, 200);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:Categories,id',
            'stock' => 'nullable|integer|min:0',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $product = Product::create([
                'name' => $validated['name'],
                'price' => $validated['price'],
                'category_id' => $validated['category_id'] ?? null,
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

            return response()->json($product, 201);
        });
    }

    public function show($id)
    {
        $product = Product::with('inventory')->findOrFail($id);
        $product->stock = $product->inventory ? $product->inventory->stock : 0;
        return response()->json($product, 200);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:Categories,id',
            'stock' => 'nullable|integer|min:0',
        ]);

        return DB::transaction(function () use ($validated, $request, $id) {
            $product = Product::findOrFail($id);
            $product->update([
                'name' => $validated['name'],
                'price' => $validated['price'],
                'category_id' => $validated['category_id'] ?? $product->category_id,
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

            $product->load('inventory');
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