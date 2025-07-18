<!-- resources/views/filament/pages/purchase-invoice.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Order Invoice - {{ $purchaseOrder->po_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .invoice-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .company-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .invoice-title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .invoice-info, .supplier-info {
            width: 48%;
        }
        
        .info-title {
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .info-row {
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 100px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .items-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .total-section {
            float: right;
            width: 300px;
            margin-top: 10px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding: 5px 0;
        }
        
        .total-row.final {
            border-top: 2px solid #000;
            font-weight: bold;
            font-size: 14px;
        }
        
        .footer {
            clear: both;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        
        .signature-box {
            width: 200px;
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 60px;
            padding-top: 5px;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 15px;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <div class="company-name">
            <span style="color: #2563eb;">HEAVEN</span>
            <span style="color: #9333ea;">SPOT</span>
            <span style="color: #059669;">INDO</span>
        </div>
        <div class="company-subtitle">Premium Paint Management System</div>
        <div class="invoice-title">PURCHASE ORDER</div>
    </div>

    <div class="invoice-details">
        <div class="invoice-info">
            <div class="info-title">PURCHASE ORDER INFO</div>
            <div class="info-row">
                <span class="info-label">PO Number:</span>
                {{ $purchaseOrder->po_number }}
            </div>
            <div class="info-row">
                <span class="info-label">Date:</span>
                {{ $purchaseOrder->purchase_date->format('d/m/Y') }}
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                {{ ucfirst($purchaseOrder->status) }}
            </div>
            <div class="info-row">
                <span class="info-label">Created:</span>
                {{ $purchaseOrder->created_at->format('d/m/Y H:i') }}
            </div>
        </div>
        
        <div class="supplier-info">
            <div class="info-title">SUPPLIER INFO</div>
            <div class="info-row">
                <span class="info-label">Name:</span>
                {{ $purchaseOrder->supplier->name }}
            </div>
            @if($purchaseOrder->supplier->contact_person)
            <div class="info-row">
                <span class="info-label">Contact:</span>
                {{ $purchaseOrder->supplier->contact_person }}
            </div>
            @endif
            @if($purchaseOrder->supplier->phone)
            <div class="info-row">
                <span class="info-label">Phone:</span>
                {{ $purchaseOrder->supplier->phone }}
            </div>
            @endif
            @if($purchaseOrder->supplier->email)
            <div class="info-row">
                <span class="info-label">Email:</span>
                {{ $purchaseOrder->supplier->email }}
            </div>
            @endif
            @if($purchaseOrder->supplier->address)
            <div class="info-row">
                <span class="info-label">Address:</span>
                {{ $purchaseOrder->supplier->address }}
            </div>
            @endif
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">Code</th>
                <th style="width: 35%;">Product Name</th>
                <th style="width: 10%;">Qty</th>
                <th style="width: 15%;">Unit Price</th>
                <th style="width: 20%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseOrder->items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->product->code }}</td>
                <td>{{ $item->product->name }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($item->total_price, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
    <div class="total-row">
        <span>Subtotal:</span>
        <span>Rp {{ number_format($purchaseOrder->subtotal, 0, ',', '.') }}</span>
    </div>
    
    @if($purchaseOrder->discount_percentage > 0)
    <div class="total-row">
        <span>Diskon ({{ $purchaseOrder->discount_percentage }}%):</span>
        <span>- Rp {{ number_format($purchaseOrder->discount_amount, 0, ',', '.') }}</span>
    </div>
    @endif
    
    @if($purchaseOrder->tax_percentage > 0)
    <div class="total-row">
        <span>PPN ({{ $purchaseOrder->tax_percentage }}%):</span>
        <span>Rp {{ number_format($purchaseOrder->tax_amount, 0, ',', '.') }}</span>
    </div>
    @endif
    
    <div class="total-row final">
        <span>GRAND TOTAL:</span>
        <span>Rp {{ number_format($purchaseOrder->grand_total, 0, ',', '.') }}</span>
    </div>
</div>

    @if($purchaseOrder->notes)
    <div class="footer">
        <div class="info-title">NOTES</div>
        <p>{{ $purchaseOrder->notes }}</p>
    </div>
    @endif

    <div class="signature-section">
        <div class="signature-box">
            <div>Prepared by:</div>
            <div class="signature-line">{{ auth()->user()->name }}</div>
        </div>
        <div class="signature-box">
            <div>Approved by:</div>
            <div class="signature-line">Manager</div>
        </div>
        <div class="signature-box">
            <div>Supplier:</div>
            <div class="signature-line">{{ $purchaseOrder->supplier->name }}</div>
        </div>
    </div>

    <div class="footer">
        <p style="text-align: center; color: #666; font-size: 10px;">
            This is a computer generated document. No signature required.<br>
            Printed on {{ now()->format('d/m/Y H:i') }} by {{ auth()->user()->name }}
        </p>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="background: #2563eb; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
            Print Invoice
        </button>
        <button onclick="window.close()" style="background: #6b7280; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
            Close
        </button>
    </div>
</body>
</html>