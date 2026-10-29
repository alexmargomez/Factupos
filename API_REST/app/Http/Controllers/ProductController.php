<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductCatalogService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductCatalogService $catalogService = new ProductCatalogService()
    ) {}

    public function index()
    {
        return response()->json($this->catalogService->listAllWithStock(), 200);
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

        $product = $this->catalogService->createProductWithInitialStock($validated, $request->has('stock'));
        return response()->json($product, 201);
    }

    public function show($id)
    {
        return response()->json($this->catalogService->findWithStockOrFail($id), 200);
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

        $product = $this->catalogService->updateProductAndSyncKardex($id, $validated, $request->has('stock'));
        return response()->json($product, 200);
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

        $product = $this->catalogService->registerMerchandisePurchase($id, $validated);
        return response()->json($product, 200);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Producto eliminado exitosamente.'], 200);
    }
}