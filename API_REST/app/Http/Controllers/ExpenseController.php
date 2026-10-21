<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::orderBy('id', 'desc')->get();
        return response()->json($expenses, 200);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'nullable|string|max:50',
            'expense_date' => 'nullable|date',
        ]);

        $expense = Expense::create([
            'description' => $validated['description'],
            'category' => $validated['category'] ?? 'General',
            'amount' => (int) $validated['amount'],
            'payment_method' => $validated['payment_method'] ?? 'Efectivo',
            'expense_date' => $validated['expense_date'] ?? now()->toDateString(),
        ]);

        return response()->json($expense, 201);
    }

    public function destroy($id)
    {
        $expense = Expense::findOrFail($id);
        $expense->delete();
        return response()->json(null, 204);
    }
}
