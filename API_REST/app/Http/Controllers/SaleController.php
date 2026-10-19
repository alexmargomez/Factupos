<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Service;
use App\Models\Invoice;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Worker;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::with(['customer', 'vehicle', 'invoice', 'saleDetails.product', 'services', 'worker'])
            ->orderBy('id', 'desc')
            ->get();
        return response()->json($sales, 200);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:Customers,id',
            'vehicle_id' => 'nullable|integer',
            'worker_id' => 'nullable|integer',
            'total' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:50',
            'products' => 'nullable|array',
            'services' => 'nullable|array',
            'create_invoice' => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $sale = Sale::create([
                'customer_id' => $validated['customer_id'],
                'vehicle_id' => $validated['vehicle_id'] ?? null,
                'worker_id' => $validated['worker_id'] ?? null,
                'total' => $validated['total'],
                'payment_method' => $validated['payment_method'],
            ]);

            if (!empty($validated['products']) && is_array($validated['products'])) {
                foreach ($validated['products'] as $prod) {
                    $qty = (int) ($prod['quantity'] ?? $prod['und'] ?? 1);
                    $priceTotal = (int) ($prod['price_total'] ?? $prod['total'] ?? 0);
                    $productId = (int) $prod['id'];

                    SaleDetail::create([
                        'customer_id' => $sale->customer_id,
                        'sale_id' => $sale->id,
                        'product_id' => $productId,
                        'quantity' => $qty,
                        'price_total' => $priceTotal,
                    ]);

                    $inventory = Inventory::firstOrCreate(
                        ['product_id' => $productId],
                        ['stock' => 0]
                    );
                    $inventory->stock = max(0, (int) $inventory->stock - $qty);
                    $inventory->save();

                    InventoryMovement::create([
                        'inventory_id' => $inventory->id,
                        'movement_type' => 'outgoing',
                        'quantity' => $qty,
                        'movement_date' => now(),
                        'reason' => 'Venta #' . $sale->id,
                    ]);
                }
            }

            $totalServicio = 0;
            if (!empty($validated['services']) && is_array($validated['services'])) {
                foreach ($validated['services'] as $srv) {
                    $srvPrice = (int) ($srv['price'] ?? 0);
                    Service::create([
                        'customer_id' => $sale->customer_id,
                        'vehicle_id' => $sale->vehicle_id,
                        'sale_id' => $sale->id,
                        'date' => $srv['date'] ?? 'Servicio general',
                        'price' => $srvPrice,
                    ]);
                    $totalServicio += $srvPrice;
                }
            }

            if (!empty($validated['create_invoice'])) {
                Invoice::create(['sale_id' => $sale->id]);
            }

            if (!empty($sale->worker_id) && $totalServicio > 0) {
                $worker = Worker::find($sale->worker_id);
                if ($worker) {
                    $worker->totalDia = ((int) $worker->totalDia) + $totalServicio;
                    $worker->save();
                }
            }

            $sale->load(['customer', 'vehicle', 'invoice', 'saleDetails.product', 'services', 'worker']);

            return response()->json($sale, 201);
        });
    }

    public function show($id)
    {
        $sale = Sale::with(['customer', 'vehicle', 'invoice', 'saleDetails.product', 'services', 'worker'])->findOrFail($id);
        return response()->json($sale, 200);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer',
            'vehicle_id' => 'nullable|integer',
            'worker_id' => 'nullable|integer',
            'total' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:50',
        ]);
        $sale = Sale::findOrFail($id);
        $sale->update($validated);

        return response()->json($sale, 200);
    }

    public function destroy($id)
    {
        $sale = Sale::findOrFail($id);
        $sale->delete();
        return response()->json(null, 204);
    }
}
