<!-- resources/views/reports/pdf-template.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ ucfirst(str_replace('_', ' ', $reportType)) }} - Heaven Spot Indo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        
        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }
        
        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .report-period {
            font-size: 12px;
            color: #666;
        }
        
        .stats-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .stat-item {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 15px;
            border: 1px solid #ddd;
            background-color: #f8f9fa;
        }
        
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #2563eb;
        }
        
        .stat-label {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th {
            background-color: #f1f5f9;
            border: 1px solid #ddd;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
        }
        
        td {
            border: 1px solid #ddd;
            padding: 8px;
            font-size: 10px;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }
        
        .footer-left {
            float: left;
        }
        
        .footer-right {
            float: right;
        }
        
        .status-normal { color: #16a34a; }
        .status-low { color: #eab308; }
        .status-out { color: #dc2626; }
        .type-in { color: #16a34a; }
        .type-out { color: #dc2626; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">HEAVEN SPOT INDO</div>
        <div>Premium Paint Management System</div>
        <div class="report-title">
            @if($reportType === 'stock')
                LAPORAN STOK PRODUK
            @elseif($reportType === 'sales')
                LAPORAN PENJUALAN
            @elseif($reportType === 'purchase')
                LAPORAN PEMBELIAN
            @else
                LAPORAN PERGERAKAN STOK
            @endif
        </div>
        @if(isset($reportData['period']))
            <div class="report-period">
                Periode: {{ \Carbon\Carbon::parse($reportData['period']['start'])->format('d/m/Y') }} - 
                {{ \Carbon\Carbon::parse($reportData['period']['end'])->format('d/m/Y') }}
            </div>
        @endif
        <div class="report-period">
            Dicetak pada: {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
    
    @if($reportType === 'stock')
        <div class="stats-section">
            <div class="stat-item">
                <div class="stat-value">{{ $reportData['total_products'] }}</div>
                <div class="stat-label">Total Produk</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $reportData['low_stock_products']->count() }}</div>
                <div class="stat-label">Stok Menipis</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $reportData['out_of_stock_products']->count() }}</div>
                <div class="stat-label">Stok Habis</div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Stok Saat Ini</th>
                    <th>Stok Minimum</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['products'] as $product)
                    <tr>
                        <td>{{ $product->code }}</td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->category->name }}</td>
                        <td>{{ $product->current_stock }}</td>
                        <td>{{ $product->minimum_stock }}</td>
                        <td>
                            @if($product->current_stock <= 0)
                                <span class="status-out">Habis</span>
                            @elseif($product->current_stock <= $product->minimum_stock)
                                <span class="status-low">Menipis</span>
                            @else
                                <span class="status-normal">Normal</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    
    @if($reportType === 'sales')
        <div class="stats-section">
            <div class="stat-item">
                <div class="stat-value">{{ $reportData['total_sales'] }}</div>
                <div class="stat-label">Total Transaksi</div>
            </div>
            <div class="stat-item" style="width: 66.66%;">
                <div class="stat-value">Rp {{ number_format($reportData['total_amount'], 0, ',', '.') }}</div>
                <div class="stat-label">Total Penjualan</div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>No. SO</th>
                    <th>Customer</th>
                    <th>Tanggal</th>
                    <th>Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['sales'] as $sale)
                    <tr>
                        <td>{{ $sale->so_number }}</td>
                        <td>{{ $sale->customer->name }}</td>
                        <td>{{ $sale->sale_date->format('d/m/Y') }}</td>
                        <td>Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    
    @if($reportType === 'purchase')
        <div class="stats-section">
            <div class="stat-item">
                <div class="stat-value">{{ $reportData['total_purchases'] }}</div>
                <div class="stat-label">Total Transaksi</div>
            </div>
            <div class="stat-item" style="width: 66.66%;">
                <div class="stat-value">Rp {{ number_format($reportData['total_amount'], 0, ',', '.') }}</div>
                <div class="stat-label">Total Pembelian</div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>No. PO</th>
                    <th>Supplier</th>
                    <th>Tanggal</th>
                    <th>Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['purchases'] as $purchase)
                    <tr>
                        <td>{{ $purchase->po_number }}</td>
                        <td>{{ $purchase->supplier->name }}</td>
                        <td>{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                        <td>Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    
    @if($reportType === 'stock_movement')
        <div class="stats-section">
            <div class="stat-item">
                <div class="stat-value">{{ $reportData['total_movements'] }}</div>
                <div class="stat-label">Total Pergerakan</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $reportData['total_in'] }}</div>
                <div class="stat-label">Stok Masuk</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $reportData['total_out'] }}</div>
                <div class="stat-label">Stok Keluar</div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Produk</th>
                    <th>Tipe</th>
                    <th>Qty</th>
                    <th>Stok Akhir</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['movements'] as $movement)
                    <tr>
                        <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $movement->product->name }}</td>
                        <td>
                            @if($movement->type === 'in')
                                <span class="type-in">Masuk</span>
                            @else
                                <span class="type-out">Keluar</span>
                            @endif
                        </td>
                        <td>{{ $movement->quantity }}</td>
                        <td>{{ $movement->current_stock }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    
    <div class="footer">
        <div class="footer-left">
            <strong>Dicetak oleh:</strong> {{ auth()->user()->name }}<br>
            <strong>Tanggal:</strong> {{ now()->format('d/m/Y H:i') }}
        </div>
        <div class="footer-right">
            <strong>Heaven Spot Indo</strong><br>
            Premium Paint Management System
        </div>
        <div style="clear: both;"></div>
    </div>
</body>
</html>