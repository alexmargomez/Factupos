<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Sale;
use Barryvdh\Snappy\Facades\SnappyPdf;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with(['sale.customer', 'sale.vehicle', 'sale.worker'])
            ->orderBy('id', 'desc')
            ->get();
        return response()->json($invoices, 200);
    }
    
    public function create(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'required|integer|exists:Sales,id',
        ]);
        
        $invoice = Invoice::firstOrCreate(['sale_id' => $validated['sale_id']]);
        return response()->json($invoice, 201);
    }

    public function show($id)
    {
        $invoice = Invoice::with([
            'sale.customer',
            'sale.vehicle',
            'sale.saleDetails.product',
            'sale.services',
            'sale.worker'
        ])->where('sale_id', $id)->get();

        return response()->json($invoice, 200);
    }

    public function showPdf($id)
    {
        $invoice = Invoice::findOrFail($id);

        $sale = Sale::with(['customer', 'vehicle', 'saleDetails.product', 'services', 'worker'])
            ->findOrFail($invoice->sale_id);

        $logoBase64 = null;
        $svgPath = public_path('img/electrimotosSm.svg');
        if (file_exists($svgPath)) {
            $logoBase64 = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($svgPath));
        }

        $data = [
            'invoice' => $invoice,
            'sale' => $sale,
            'customer' => $sale->customer,
            'vehicle' => $sale->vehicle,
            'saleDetails' => $sale->saleDetails,
            'services' => $sale->services,
            'worker' => $sale->worker,
            'logoBase64' => $logoBase64,
        ];

        $binary = config('snappy.pdf.binary', '/usr/bin/wkhtmltopdf');
        if (is_string($binary) && file_exists($binary) && is_executable($binary)) {
            try {
                $pdf = SnappyPdf::loadView('invoices.show', $data);
                return $pdf->stream("invoice_{$id}.pdf", ['Attachment' => false]);
            } catch (\Throwable $e) {
                // Fallback a vista HTML imprimible si wkhtmltopdf falla
            }
        }

        return response()
            ->view('invoices.show', $data, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'sale_id' => 'required|integer|exists:Sales,id',
        ]);
        $invoice = Invoice::findOrFail($id);
        $invoice->update($validated);

        return response()->json($invoice, 200);
    }

    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->delete();
        return response()->json(null, 204);
    }
}
