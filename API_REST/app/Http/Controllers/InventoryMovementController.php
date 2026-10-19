<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryMovement;


class InventoryMovementController extends Controller
{
    public function index()
    {
        $inventory_movements = InventoryMovement::with('inventory.product')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($movement) {
                $movement->product_name = optional(optional($movement->inventory)->product)->name ?? 'Producto #' . $movement->inventory_id;
                $movement->product_id = optional($movement->inventory)->product_id;
                return $movement;
            });

        return response()->json($inventory_movements, 200);
    }
    
    public function create(Request $request)
    {
        $validated = $request->validate([
            'inventory_id' => 'required|integer|exists:Inventory,id',
            'movement_type' => 'required|in:incoming,outgoing',
            'quantity' => 'required|integer|min:1',
            'movement_date' => 'nullable|date',
            'reason' => 'nullable|string|max:255',
        ]);
        
        $inventory_movement = InventoryMovement::create([
            'inventory_id' => $validated['inventory_id'],
            'movement_type' => $validated['movement_type'],
            'quantity' => $validated['quantity'],
            'movement_date' => $validated['movement_date'] ?? now(),
            'reason' => $validated['reason'] ?? 'Movimiento de inventario',
        ]);
        return response()->json($inventory_movement, 201);
    }

    public function show($id)
    {
        $inventory_movement = InventoryMovement::with('inventory.product')->findOrFail($id);
        return response()->json($inventory_movement, 200);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'inventory_id' => 'required|integer',
            'movement_type' => 'required|in:incoming,outgoing',
            'quantity' => 'required|integer|min:1',
            'movement_date' => 'nullable|date',
            'reason' => 'nullable|string|max:255',
        ]);
        $inventory_movement = InventoryMovement::find($id) ?? InventoryMovement::where('inventory_id', $id)->firstOrFail();
        $inventory_movement->update($validated);

        return response()->json($inventory_movement, 200);
    }

    public function destroy($id)
    {
        $inventory_movement = InventoryMovement::findOrFail($id);
        $inventory_movement->delete();
        return response()->json(null, 204);
    }
}
