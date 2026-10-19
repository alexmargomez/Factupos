<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index()
    {
        $inventory = Inventory::with('product')->get();
        return response()->json($inventory, 200);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'stock' => 'required|integer|min:0',
            'product_id' => 'required|integer|exists:Products,id',
        ]);
        
        return DB::transaction(function () use ($validated) {
            $existing = Inventory::where('product_id', $validated['product_id'])->first();
            $oldStock = $existing ? (int) $existing->stock : 0;

            $inventory = Inventory::updateOrCreate(
                ['product_id' => $validated['product_id']],
                ['stock' => (int) $validated['stock']]
            );

            $diff = (int) $validated['stock'] - $oldStock;
            if ($diff !== 0) {
                InventoryMovement::create([
                    'inventory_id' => $inventory->id,
                    'movement_type' => $diff > 0 ? 'incoming' : 'outgoing',
                    'quantity' => abs($diff),
                    'movement_date' => now(),
                    'reason' => $existing ? 'Actualización de stock' : 'Inventario inicial',
                ]);
            }

            return response()->json($inventory, 201);
        });
    }

    public function stock($id)
    {
        $inventory = Inventory::firstOrCreate(
            ['product_id' => $id],
            ['stock' => 0]
        );
        return response()->json($inventory, 200);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'stock' => 'required|integer|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($validated, $id) {
            $inventory = Inventory::firstOrCreate(
                ['product_id' => $id],
                ['stock' => 0]
            );

            $oldStock = (int) $inventory->stock;
            $newStock = (int) $validated['stock'];
            $diff = $newStock - $oldStock;

            $inventory->stock = $newStock;
            $inventory->save();

            if ($diff !== 0) {
                InventoryMovement::create([
                    'inventory_id' => $inventory->id,
                    'movement_type' => $diff > 0 ? 'incoming' : 'outgoing',
                    'quantity' => abs($diff),
                    'movement_date' => now(),
                    'reason' => $validated['reason'] ?? ($diff > 0 ? 'Entrada manual de inventario' : 'Salida manual de inventario'),
                ]);
            }

            return response()->json($inventory, 200);
        });
    }
}
