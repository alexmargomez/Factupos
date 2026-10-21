<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::with('customer')->orderBy('id', 'desc')->get();
        return response()->json($vehicles, 200);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'plate' => 'required|string|max:20',
            'model' => 'required|string|max:100',
            'make' => 'required|string|max:100',
            'customer_id' => 'required|exists:Customers,id',
            'current_mileage' => 'nullable|integer|min:0',
            'next_oil_change_km' => 'nullable|integer|min:0',
            'soat_due_date' => 'nullable|date',
            'tecno_due_date' => 'nullable|date',
        ]);

        $vehicle = Vehicle::create($validated);
        return response()->json($vehicle->load('customer'), 201);
    }

    public function show($id)
    {
        $vehicle = Vehicle::with('customer')->findOrFail($id);
        return response()->json($vehicle, 200);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'plate' => 'sometimes|required|string|max:20',
            'model' => 'sometimes|required|string|max:100',
            'make' => 'sometimes|required|string|max:100',
            'customer_id' => 'nullable|exists:Customers,id',
            'current_mileage' => 'nullable|integer|min:0',
            'next_oil_change_km' => 'nullable|integer|min:0',
            'soat_due_date' => 'nullable|date',
            'tecno_due_date' => 'nullable|date',
        ]);
        $vehicle = Vehicle::findOrFail($id);

        $payload = [];
        foreach (['plate', 'model', 'make', 'customer_id', 'current_mileage', 'next_oil_change_km', 'soat_due_date', 'tecno_due_date'] as $field) {
            if ($request->exists($field)) {
                $payload[$field] = $validated[$field] ?? null;
            }
        }
        $vehicle->update($payload);

        return response()->json($vehicle->load('customer'), 200);
    }

    public function destroy($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->delete();
    
        return response()->json(['message' => 'Vehículo eliminado exitosamente.'], 200);
    }
}
