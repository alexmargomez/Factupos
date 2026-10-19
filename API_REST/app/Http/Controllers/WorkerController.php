<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use Illuminate\Http\Request;

class WorkerController extends Controller
{
    public function index()
    {
        $workers = Worker::orderBy('id', 'asc')->get();
        return response()->json($workers, 200);
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
        ]);

        $worker = Worker::create([
            'name' => $validated['name'],
            'totalDia' => $validated['totalDia'] ?? 0,
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

        if ($request->has('reset_total') && $request->boolean('reset_total')) {
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
