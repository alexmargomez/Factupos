<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use App\Models\WorkerSettlement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkerController extends Controller
{
    public function index()
    {
        $workers = Worker::orderBy('id', 'asc')->get();
        return response()->json($workers, 200);
    }

    public function settlements()
    {
        $settlements = WorkerSettlement::orderBy('id', 'desc')->get();
        return response()->json($settlements, 200);
    }

    public function show($id)
    {
        $worker = Worker::find($id);
        if ($worker) {
            return response()->json($worker, 200);
        }
        return response()->json(['message' => 'Worker not found'], 404);
    }

    public function create(Request $request)
    {
        return $this->store($request);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'totalDia' => 'nullable|numeric|min:0',
            'commission_percentage' => 'nullable|integer|min:0|max:100',
        ]);

        $worker = Worker::create([
            'name' => $validated['name'],
            'totalDia' => $validated['totalDia'] ?? 0,
            'commission_percentage' => $validated['commission_percentage'] ?? 50,
        ]);
        return response()->json($worker, 201);
    }

    public function update(Request $request, $id)
    {
        $worker = Worker::find($id);
        if (!$worker) {
            return response()->json(['message' => 'Worker not found'], 404);
        }

        if ($request->has('name')) {
            $worker->name = $request->input('name');
        }

        if ($request->has('commission_percentage')) {
            $pct = max(0, min(100, (int) $request->input('commission_percentage')));
            $worker->commission_percentage = $pct;
        }

        if (
            ($request->has('reset_total') && $request->boolean('reset_total')) ||
            ($request->has('reset') && $request->boolean('reset'))
        ) {
            $worker->totalDia = 0;
        } elseif ($request->has('totalDia')) {
            $amount = (int) $request->input('totalDia');
            if ($request->boolean('replace_total')) {
                $worker->totalDia = $amount;
            } else {
                $worker->totalDia = ((int) $worker->totalDia) + $amount;
            }
        }

        $worker->save();
        return response()->json($worker, 200);
    }

    public function settle(Request $request, $id)
    {
        $worker = Worker::findOrFail($id);

        return DB::transaction(function () use ($request, $worker) {
            $gross = (int) $worker->totalDia;
            $pct = $request->has('commission_percentage')
                ? max(0, min(100, (int) $request->input('commission_percentage')))
                : (int) ($worker->commission_percentage ?? 50);

            $paid = (int) round(($gross * $pct) / 100);
            $workshopShare = max(0, $gross - $paid);

            $settlement = WorkerSettlement::create([
                'worker_id' => $worker->id,
                'worker_name' => $worker->name,
                'gross_amount' => $gross,
                'commission_percentage' => $pct,
                'paid_amount' => $paid,
                'workshop_share' => $workshopShare,
                'settlement_date' => now()->toDateString(),
                'notes' => $request->input('notes') ?: 'Liquidación de turno',
            ]);

            $worker->totalDia = 0;
            $worker->commission_percentage = $pct;
            $worker->save();

            return response()->json([
                'worker' => $worker,
                'settlement' => $settlement,
            ], 201);
        });
    }

    public function destroy($id)
    {
        $worker = Worker::find($id);
        if ($worker) {
            $worker->delete();
            return response()->json(['message' => 'Worker deleted'], 200);
        }
        return response()->json(['message' => 'Worker not found'], 404);
    }
}
