<!-- resources/views/filament/pages/report-page.blade.php -->
<x-filament-panels::page>
    <style>
        /* Dark mode support */
        .dark .report-card {
            background-color: rgb(31 41 55) !important;
            border-color: rgb(55 65 81) !important;
            color: rgb(243 244 246) !important;
        }
        
        .dark .report-card h3 {
            color: rgb(243 244 246) !important;
        }
        
        .dark .report-card p {
            color: rgb(156 163 175) !important;
        }
        
        .dark .report-table {
            background-color: rgb(31 41 55) !important;
            border-color: rgb(55 65 81) !important;
        }
        
        .dark .report-table thead {
            background-color: rgb(55 65 81) !important;
        }
        
        .dark .report-table th {
            color: rgb(209 213 219) !important;
            border-color: rgb(75 85 99) !important;
        }
        
        .dark .report-table td {
            color: rgb(243 244 246) !important;
            border-color: rgb(75 85 99) !important;
        }
        
        .dark .report-table tbody tr:nth-child(even) {
            background-color: rgb(55 65 81) !important;
        }
        
        .dark .stat-card {
            background-color: rgb(31 41 55) !important;
            border-color: rgb(55 65 81) !important;
        }
        
        .dark .empty-state {
            color: rgb(156 163 175) !important;
        }
        
        .dark .empty-state h3 {
            color: rgb(243 244 246) !important;
        }
        
        /* Improved spacing */
        .form-spacing {
            margin-bottom: 2rem !important;
        }
        
        .generate-button {
            margin-top: 1.5rem !important;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                font-size: 12px;
                line-height: 1.4;
            }
        }
    </style>

    <div class="space-y-6">
        <!-- Form Section -->
        <div class="form-spacing">
            <form wire:submit="generateReport" class="no-print">
                {{ $this->form }}
                
                <div class="flex justify-end generate-button">
                    <x-filament::button type="submit" size="lg">
                        Generate Laporan
                    </x-filament::button>
                </div>
            </form>
        </div>

        <!-- Report Results -->
        @if($this->reportData)
            <div class="mt-8">
                <!-- Stock Report -->
                @if($this->reportData['type'] === 'stock')
                    <div class="report-card bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4">Laporan Stok</h3>
                        
                        @if($this->reportData['total_products'] > 0)
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded">
                                    <div class="text-2xl font-bold text-blue-800 dark:text-blue-300">{{ $this->reportData['total_products'] }}</div>
                                    <div class="text-blue-600 dark:text-blue-400">Total Produk</div>
                                </div>
                                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded">
                                    <div class="text-2xl font-bold text-yellow-800 dark:text-yellow-300">{{ $this->reportData['low_stock_products']->count() }}</div>
                                    <div class="text-yellow-600 dark:text-yellow-400">Stok Menipis</div>
                                </div>
                                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded">
                                    <div class="text-2xl font-bold text-red-800 dark:text-red-300">{{ $this->reportData['out_of_stock_products']->count() }}</div>
                                    <div class="text-red-600 dark:text-red-400">Stok Habis</div>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="report-table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kode</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nama Produk</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kategori</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Stok Saat Ini</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Stok Minimum</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($this->reportData['products'] as $product)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $product->code }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $product->name }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $product->category->name }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $product->current_stock }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $product->minimum_stock }}</td>
                                                <td class="px-6 py-4 text-sm">
                                                    @if($product->current_stock <= 0)
                                                        <span class="text-red-600 dark:text-red-400">Habis</span>
                                                    @elseif($product->current_stock <= $product->minimum_stock)
                                                        <span class="text-yellow-600 dark:text-yellow-400">Menipis</span>
                                                    @else
                                                        <span class="text-green-600 dark:text-green-400">Normal</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="empty-state text-center py-12">
                                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                                    <svg class="h-10 w-10 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Belum Ada Data Produk</h3>
                                <p class="text-gray-500 dark:text-gray-400">Sistem belum memiliki data produk untuk ditampilkan.</p>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Sales Report -->
                @if($this->reportData['type'] === 'sales')
                    <div class="report-card bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4">Laporan Penjualan</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Periode: {{ \Carbon\Carbon::parse($this->reportData['period']['start'])->format('d/m/Y') }} - 
                            {{ \Carbon\Carbon::parse($this->reportData['period']['end'])->format('d/m/Y') }}
                        </p>
                        
                        @if($this->reportData['total_sales'] > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded">
                                    <div class="text-2xl font-bold text-blue-800 dark:text-blue-300">{{ $this->reportData['total_sales'] }}</div>
                                    <div class="text-blue-600 dark:text-blue-400">Total Transaksi</div>
                                </div>
                                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded">
                                    <div class="text-2xl font-bold text-green-800 dark:text-green-300">Rp {{ number_format($this->reportData['total_amount'], 0, ',', '.') }}</div>
                                    <div class="text-green-600 dark:text-green-400">Total Penjualan</div>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="report-table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">No. SO</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Customer</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tanggal</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($this->reportData['sales'] as $sale)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $sale->so_number }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $sale->customer->name }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $sale->sale_date->format('d/m/Y') }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="empty-state text-center py-12">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Tidak Ada Data Penjualan</h3>
                                <p class="text-gray-500 dark:text-gray-400">Tidak ada transaksi penjualan pada periode tersebut.</p>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Purchase Report -->
                @if($this->reportData['type'] === 'purchase')
                    <div class="report-card bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4">Laporan Pembelian</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Periode: {{ \Carbon\Carbon::parse($this->reportData['period']['start'])->format('d/m/Y') }} - 
                            {{ \Carbon\Carbon::parse($this->reportData['period']['end'])->format('d/m/Y') }}
                        </p>
                        
                        @if($this->reportData['total_purchases'] > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded">
                                    <div class="text-2xl font-bold text-blue-800 dark:text-blue-300">{{ $this->reportData['total_purchases'] }}</div>
                                    <div class="text-blue-600 dark:text-blue-400">Total Transaksi</div>
                                </div>
                                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded">
                                    <div class="text-2xl font-bold text-green-800 dark:text-green-300">Rp {{ number_format($this->reportData['total_amount'], 0, ',', '.') }}</div>
                                    <div class="text-green-600 dark:text-green-400">Total Pembelian</div>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="report-table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">No. PO</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Supplier</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tanggal</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($this->reportData['purchases'] as $purchase)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $purchase->po_number }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $purchase->supplier->name }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="empty-state text-center py-12">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Tidak Ada Data Pembelian</h3>
                                <p class="text-gray-500 dark:text-gray-400">Tidak ada transaksi pembelian pada periode tersebut.</p>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Stock Movement Report -->
                @if($this->reportData['type'] === 'stock_movement')
                    <div class="report-card bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4">Laporan Pergerakan Stok</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Periode: {{ \Carbon\Carbon::parse($this->reportData['period']['start'])->format('d/m/Y') }} - 
                            {{ \Carbon\Carbon::parse($this->reportData['period']['end'])->format('d/m/Y') }}
                        </p>
                        
                        @if($this->reportData['total_movements'] > 0)
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded">
                                    <div class="text-2xl font-bold text-blue-800 dark:text-blue-300">{{ $this->reportData['total_movements'] }}</div>
                                    <div class="text-blue-600 dark:text-blue-400">Total Pergerakan</div>
                                </div>
                                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded">
                                    <div class="text-2xl font-bold text-green-800 dark:text-green-300">{{ $this->reportData['total_in'] }}</div>
                                    <div class="text-green-600 dark:text-green-400">Stok Masuk</div>
                                </div>
                                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded">
                                    <div class="text-2xl font-bold text-red-800 dark:text-red-300">{{ $this->reportData['total_out'] }}</div>
                                    <div class="text-red-600 dark:text-red-400">Stok Keluar</div>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="report-table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tanggal</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Produk</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tipe</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Qty</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Stok Akhir</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($this->reportData['movements'] as $movement)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $movement->product->name }}</td>
                                                <td class="px-6 py-4 text-sm">
                                                    @if($movement->type === 'in')
                                                        <span class="text-green-600 dark:text-green-400">Masuk</span>
                                                    @else
                                                        <span class="text-red-600 dark:text-red-400">Keluar</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $movement->quantity }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $movement->current_stock }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="empty-state text-center py-12">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Tidak Ada Pergerakan Stok</h3>
                                <p class="text-gray-500 dark:text-gray-400">Tidak ada pergerakan stok pada periode tersebut.</p>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Export Buttons -->
                <div class="flex justify-center space-x-4 mt-6 no-print">
                    <button onclick="exportToPDF()" class="bg-red-500 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700 text-white font-medium py-2.5 px-6 rounded-lg flex items-center space-x-2 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                        </svg>
                        <span>Export PDF</span>
                    </button>
                    <button onclick="exportToExcel()" class="bg-green-500 hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-700 text-white font-medium py-2.5 px-6 rounded-lg flex items-center space-x-2 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Export Excel</span>
                    </button>
                </div>
            </div>
        @endif
    </div>

    <script>
        function exportToPDF() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("reports.export.pdf") }}';
            form.target = '_blank';
            
            // Add CSRF token
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            // Add form data
            const formData = @this.data;
            Object.keys(formData).forEach(key => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = formData[key];
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
        
        function exportToExcel() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("reports.export.excel") }}';
            
            // Add CSRF token
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            // Add form data
            const formData = @this.data;
            Object.keys(formData).forEach(key => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = formData[key];
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
    </script>
</x-filament-panels::page>