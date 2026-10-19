<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|integer',
            'date' => 'required|string',
            'price' => 'required|numeric|min:0',
            'customer_id' => 'required|integer',
            'vehicle_id' => 'nullable|integer',
        ]);

        $service = Service::create($request->all());

        return response()->json($service->load(['vehicle', 'customer']), 201);
    }

    public function show($id)
    {
        $service = Service::with(['vehicle', 'customer'])
            ->where('sale_id', $id)
            ->orderBy('id', 'desc')
            ->get();
        return response()->json($service, 200);
    }

    public function showCustomer($id)
    {
        $service = Service::with(['vehicle', 'customer'])
            ->where('customer_id', $id)
            ->orderBy('id', 'desc')
            ->get();
        return response()->json($service, 200);
    }

    public function showVehicle($id)
    {
        $service = Service::with(['vehicle', 'customer'])
            ->where('vehicle_id', $id)
            ->orderBy('id', 'desc')
            ->get();
        return response()->json($service, 200);
    }
}
