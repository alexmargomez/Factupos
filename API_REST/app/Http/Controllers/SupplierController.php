<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::orderBy('name', 'asc')->get();
        return response()->json($suppliers, 200);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nit' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:60',
            'email' => 'nullable|string|max:150',
            'contact_name' => 'nullable|string|max:150',
        ]);

        $supplier = Supplier::create($validated);
        return response()->json($supplier, 201);
    }

    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'nit' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:60',
            'email' => 'nullable|string|max:150',
            'contact_name' => 'nullable|string|max:150',
        ]);

        $supplier->update($validated);
        return response()->json($supplier, 200);
    }

    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();
        return response()->json(null, 204);
    }
}
