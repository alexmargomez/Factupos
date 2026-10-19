<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::orderBy('id', 'desc')->get();
        return response()->json($customers, 200);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);
        
        $customer = Customer::create($validated);
        return response()->json($customer, 201);
    }

    public function show($id)
    {
        $customer = Customer::findOrFail($id);
        return response()->json($customer, 200);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);
        $customer = Customer::findOrFail($id);
        $customer->update($validated);

        return response()->json($customer, 200);
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();
    
        return response()->json(['message' => 'Cliente eliminado exitosamente.'], 200);
    }
}
