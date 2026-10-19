<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SaleDetail;
use App\Models\Inventory;
use App\Models\InventoryMovement;

class SaleDetailController extends Controller
{
    public function index()
    {
        $sale_details = SaleDetail::with('product')->orderBy('id', 'desc')->get();
        return response()->json($sale_details, 200);
    }
    
    public function create(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'required|integer|exists:Sales,id',
            'product_id' => 'required|integer|exists:Products,id',
            'customer_id' => 'required|integer|exists:Customers,id',
            'quantity' => 'required|integer|min:1',
            'price_total' => 'required|numeric|min:0',
        ]);
        
        return DB::transaction(function () use ($validated) {
            $sale_detail = SaleDetail::create($validated);

            $inventory = Inventory::firstOrCreate(
                ['product_id' => $validated['product_id']],
                ['stock' => 0]
            );

            $qty = (int) $validated['quantity'];
            $inventory->stock = max(0, (int) $inventory->stock - $qty);
            $inventory->save();

            InventoryMovement::create([
                'inventory_id' => $inventory->id,
                'movement_type' => 'outgoing',
                'quantity' => $qty,
                'movement_date' => now(),
                'reason' => 'Venta #' . $validated['sale_id'],
            ]);

            return response()->json($sale_detail, 201);
        });
    }

    public function show($id)
    {
        $sale_detail = SaleDetail::with('product')->where('sale_id', $id)->get();
        return response()->json($sale_detail, 200);
    }

    public function showCustomer($id)
    {
        $sale_detail = SaleDetail::with('product')->where('customer_id', $id)->get();
        return response()->json($sale_detail, 200);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'price_total' => 'nullable|numeric|min:0',
            'total' => 'nullable|numeric|min:0',
        ]);
        $sale_detail = SaleDetail::findOrFail($id);
        $sale_detail->update([
            'quantity' => $validated['quantity'],
            'price_total' => $validated['price_total'] ?? $validated['total'] ?? $sale_detail->price_total,
        ]);

        return response()->json($sale_detail, 200);
    }

    public function destroy($id)
    {
        $sale_detail = SaleDetail::findOrFail($id);
        $sale_detail->delete();
        return response()->json(null, 204);
    }
}
