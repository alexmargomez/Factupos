<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index()
    {
        $schedule = Schedule::with(['customer', 'vehicle', 'worker'])->orderBy('id', 'desc')->get();
        return response()->json($schedule, 200);
    }

    public function create(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:Customers,id',
            'servicios' => 'required|array',
            'vehicle_id' => 'required|exists:Vehicles,id',
            'state' => 'required|in:Pendiente,Completado',
            'worker_id' => 'required|exists:Workers,id',
        ]);

        $schedule = Schedule::create([
            'customer_id' => $request->customer_id,
            'state' => $request->state,
            'vehicle_id' => $request->vehicle_id,
            'servicios' => is_string($request->servicios) ? $request->servicios : json_encode($request->servicios),
            'worker_id' => $request->worker_id,
        ]);

        return response()->json($schedule, 201);
    }

    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        $request->validate([
            'servicios' => 'required|array',
            'state' => 'required|in:Pendiente,Completado',
        ]);

        $schedule->update([
            'servicios' => is_string($request->servicios) ? $request->servicios : json_encode($request->servicios),
            'state' => $request->state,
            'worker_id' => $request->worker_id ?? $schedule->worker_id,
        ]);

        return response()->json($schedule, 200);
    }

    public function show($id)
    {
        $schedule = Schedule::with(['customer', 'vehicle', 'worker'])->where('customer_id', $id)->get();
        return response()->json($schedule, 200);
    }
    
    public function destroy(string $id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();

        return response()->json(null, 204);
    }
}
