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
        ]);

        $vehicle = Vehicle::create($validated);
        return response()->json($vehicle, 201);
    }

    public function show($id)
    {
        $vehicle = Vehicle::with('customer')->findOrFail($id);
        return response()->json($vehicle, 200);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'plate' => 'required|string|max:20',
            'model' => 'required|string|max:100',
            'make' => 'required|string|max:100',
            'customer_id' => 'nullable|exists:Customers,id',
        ]);
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->update(array_filter($validated, fn($v) => !is_null($v)));

        return response()->json($vehicle, 200);
    }

    public function destroy($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->delete();
    
        return response()->json(['message' => 'Vehículo eliminado exitosamente.'], 200);
    }
}
