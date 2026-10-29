<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\PosBillingService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(
        private readonly PosBillingService $billingService = new PosBillingService()
    ) {}

    public function index()
    {
        $sales = Sale::with(['customer', 'vehicle', 'invoice', 'saleDetails.product', 'services', 'worker'])
            ->orderBy('id', 'desc')
            ->limit(500)
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
            'payment_status' => 'nullable|string|max:50',
            'paid_amount' => 'nullable|numeric|min:0',
            'pending_balance' => 'nullable|numeric|min:0',
            'payment_notes' => 'nullable|string|max:255',
            'products' => 'nullable|array',
            'services' => 'nullable|array',
            'create_invoice' => 'nullable|boolean',
        ]);

        $sale = $this->billingService->createSaleTransaction($validated);
        return response()->json($sale, 201);
    }

    public function show($id)
    {
        $sale = Sale::with(['customer', 'vehicle', 'invoice', 'saleDetails.product', 'services', 'worker'])->findOrFail($id);
        return response()->json($sale, 200);
    }

    public function update(Request $request, $id)
    {
        $sale = Sale::findOrFail($id);

        $validated = $request->validate([
            'customer_id' => 'sometimes|integer',
            'vehicle_id' => 'nullable|integer',
            'worker_id' => 'nullable|integer',
            'total' => 'sometimes|numeric|min:0',
            'payment_method' => 'sometimes|string|max:50',
            'payment_status' => 'nullable|string|max:50',
            'paid_amount' => 'nullable|numeric|min:0',
            'pending_balance' => 'nullable|numeric|min:0',
            'payment_notes' => 'nullable|string|max:255',
            'abono' => 'nullable|numeric|min:1',
        ]);

        $updated = $this->billingService->updateSaleTransaction($sale, $validated);
        return response()->json($updated, 200);
    }

    public function destroy($id)
    {
        $sale = Sale::findOrFail($id);
        $sale->delete();
        return response()->json(null, 204);
    }
}
