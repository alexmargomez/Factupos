<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura #{{ $invoice->invoice_number }} - Electrimotos</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 28px;
            color: #1e293b;
            line-height: 1.45;
            background-color: #ffffff;
            font-size: 13px;
        }
        .invoice-box {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 24px;
        }
        .header-table {
            width: 100%;
            border-bottom: 3px solid #023047;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .company-logo {
            max-width: 160px;
            max-height: 80px;
        }
        .company-info {
            text-align: right;
        }
        .company-info h1 {
            margin: 0 0 4px 0;
            font-size: 22px;
            color: #023047;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .company-info p {
            margin: 2px 0;
            color: #475569;
            font-size: 12px;
        }
        .invoice-badge {
            display: inline-block;
            margin-top: 8px;
            padding: 6px 12px;
            background-color: #023047;
            color: #ffffff;
            border-radius: 4px;
            font-weight: bold;
            font-size: 14px;
        }
        .info-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 12px 0;
            margin: 0 -12px 20px -12px;
        }
        .info-card {
            width: 33.33%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            vertical-align: top;
        }
        .info-card h3 {
            margin: 0 0 8px 0;
            font-size: 12px;
            text-transform: uppercase;
            color: #023047;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
        }
        .info-card p {
            margin: 4px 0;
            font-size: 12px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .items-table th {
            background-color: #023047;
            color: #ffffff;
            padding: 10px 12px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
        }
        .items-table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 12px;
            font-size: 13px;
        }
        .items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-right {
            text-align: right !important;
        }
        .text-center {
            text-align: center !important;
        }
        .badge-type {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-product {
            background-color: #e0f2fe;
            color: #0369a1;
        }
        .badge-service {
            background-color: #fef3c7;
            color: #b45309;
        }
        .totals-table {
            width: 320px;
            margin-left: auto;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 6px 10px;
            font-size: 13px;
        }
        .totals-table .grand-total td {
            border-top: 2px solid #023047;
            font-size: 17px;
            font-weight: bold;
            color: #023047;
            padding-top: 10px;
        }
        footer {
            text-align: center;
            margin-top: 32px;
            border-top: 1px solid #cbd5e1;
            padding-top: 12px;
            font-size: 11px;
            color: #64748b;
        }
        @media print {
            body {
                padding: 0;
            }
            .invoice-box {
                border: none;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    @php
        $subtotalProductos = $saleDetails ? $saleDetails->sum('price_total') : 0;
        $subtotalServicios = $services ? $services->sum('price') : 0;
    @endphp
    <div class="invoice-box">
        <table class="header-table">
            <tr>
                <td style="width: 40%;">
                    @if(!empty($logoBase64))
                        <img src="{{ $logoBase64 }}" class="company-logo" alt="Electrimotos">
                    @else
                        <h2 style="margin:0; color:#023047;">ELECTRIMOTOS</h2>
                    @endif
                </td>
                <td class="company-info" style="width: 60%;">
                    <h1>Electrimotos</h1>
                    <p>Carrera 11 #1-11 | Servicio Técnico y Repuestos</p>
                    <p>Email: info@electrimotos.com | Teléfono: 123-456-789</p>
                    <p>NIT: 123456789-0</p>
                    <div class="invoice-badge">
                        FACTURA N° {{ $invoice->invoice_number }}
                    </div>
                </td>
            </tr>
        </table>

        <table class="info-grid">
            <tr>
                <td class="info-card">
                    <h3>Datos del Cliente</h3>
                    <p><strong>Nombre:</strong> {{ optional($customer)->name ?? 'Cliente Mostrador' }}</p>
                    <p><strong>ID / Código:</strong> {{ optional($customer)->id ?? 'N/A' }}</p>
                    <p><strong>Teléfono:</strong> {{ optional($customer)->phone ?: 'No registrado' }}</p>
                    <p><strong>Correo:</strong> {{ optional($customer)->email ?: 'No registrado' }}</p>
                </td>
                <td class="info-card">
                    <h3>Datos del Vehículo</h3>
                    @if($vehicle)
                        <p><strong>Placa:</strong> {{ $vehicle->plate }}</p>
                        <p><strong>Marca:</strong> {{ $vehicle->make }}</p>
                        <p><strong>Modelo:</strong> {{ $vehicle->model }}</p>
                    @else
                        <p>Venta directa por mostrador</p>
                        <p><em>Sin vehículo asociado</em></p>
                    @endif
                </td>
                <td class="info-card">
                    <h3>Información de Venta</h3>
                    <p><strong>Fecha:</strong> {{ optional($invoice->created_at)->format('d/m/Y H:i') ?? date('d/m/Y') }}</p>
                    <p><strong>Método de Pago:</strong> {{ $sale->payment_method ?? 'Efectivo' }}</p>
                    <p><strong>Técnico / Empleado:</strong> {{ optional($worker ?? $sale->worker)->name ?? 'General' }}</p>
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Tipo</th>
                    <th style="width: 45%;">Descripción</th>
                    <th class="text-center" style="width: 12%;">Cant.</th>
                    <th class="text-right" style="width: 14%;">V. Unitario</th>
                    <th class="text-right" style="width: 14%;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($services as $service)
                <tr>
                    <td><span class="badge-type badge-service">Servicio</span></td>
                    <td>{{ $service->date }}</td>
                    <td class="text-center">1</td>
                    <td class="text-right">$ {{ number_format($service->price, 0, ',', '.') }}</td>
                    <td class="text-right"><strong>$ {{ number_format($service->price, 0, ',', '.') }}</strong></td>
                </tr>
                @endforeach
                @foreach ($saleDetails as $detail)
                @php
                    $unitPrice = $detail->quantity > 0 ? ($detail->price_total / $detail->quantity) : $detail->price_total;
                @endphp
                <tr>
                    <td><span class="badge-type badge-product">Producto</span></td>
                    <td>{{ optional($detail->product)->name ?? 'Producto #' . $detail->product_id }}</td>
                    <td class="text-center">{{ $detail->quantity }}</td>
                    <td class="text-right">$ {{ number_format($unitPrice, 0, ',', '.') }}</td>
                    <td class="text-right"><strong>$ {{ number_format($detail->price_total, 0, ',', '.') }}</strong></td>
                </tr>
                @endforeach
                @if(count($services) === 0 && count($saleDetails) === 0)
                <tr>
                    <td colspan="5" class="text-center">Sin detalles registrados</td>
                </tr>
                @endif
            </tbody>
        </table>

        <table class="totals-table">
            <tr>
                <td>Subtotal Productos:</td>
                <td class="text-right">$ {{ number_format($subtotalProductos, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Subtotal Mano de Obra / Servicios:</td>
                <td class="text-right">$ {{ number_format($subtotalServicios, 0, ',', '.') }}</td>
            </tr>
            <tr class="grand-total">
                <td>TOTAL A PAGAR:</td>
                <td class="text-right">$ {{ number_format($sale->total, 0, ',', '.') }}</td>
            </tr>
        </table>

        <footer>
            <p><strong>¡Gracias por confiar en Electrimotos!</strong> — Conserve esta factura para efectos de garantía.</p>
        </footer>
    </div>
</body>
</html>