<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Vehicle;
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
            'workshop_status' => 'nullable|string|max:50',
            'mileage' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'worker_id' => 'required|exists:Workers,id',
        ]);

        $schedule = Schedule::create([
            'customer_id' => $request->customer_id,
            'state' => $request->state,
            'workshop_status' => $request->workshop_status ?: ($request->state === 'Completado' ? 'Entregado' : 'Recibido'),
            'mileage' => $request->mileage,
            'notes' => $request->notes,
            'vehicle_id' => $request->vehicle_id,
            'servicios' => is_string($request->servicios) ? $request->servicios : json_encode($request->servicios),
            'worker_id' => $request->worker_id,
        ]);

        if (!is_null($request->mileage) && $request->vehicle_id) {
            Vehicle::where('id', $request->vehicle_id)->update([
                'current_mileage' => $request->mileage,
            ]);
        }

        return response()->json($schedule->load(['customer', 'vehicle', 'worker']), 201);
    }

    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        $request->validate([
            'servicios' => 'sometimes|array',
            'state' => 'sometimes|in:Pendiente,Completado',
            'workshop_status' => 'nullable|string|max:50',
            'mileage' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'worker_id' => 'nullable|exists:Workers,id',
        ]);

        $nextState = $request->has('state') ? $request->state : $schedule->state;
        $nextWorkshopStatus = $request->has('workshop_status')
            ? $request->workshop_status
            : ($nextState === 'Completado' ? 'Entregado' : ($schedule->workshop_status ?: 'Recibido'));

        $updatePayload = [
            'state' => $nextState,
            'workshop_status' => $nextWorkshopStatus,
            'worker_id' => $request->worker_id ?? $schedule->worker_id,
        ];

        if ($request->has('servicios')) {
            $updatePayload['servicios'] = is_string($request->servicios)
                ? $request->servicios
                : json_encode($request->servicios);
        }
        if ($request->has('mileage')) {
            $updatePayload['mileage'] = $request->mileage;
            if (!is_null($request->mileage) && $schedule->vehicle_id) {
                Vehicle::where('id', $schedule->vehicle_id)->update([
                    'current_mileage' => $request->mileage,
                ]);
            }
        }
        if ($request->has('notes')) {
            $updatePayload['notes'] = $request->notes;
        }

        $schedule->update($updatePayload);

        return response()->json($schedule->load(['customer', 'vehicle', 'worker']), 200);
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
