<!-- resources/views/filament/widgets/stock-movement-detail.blade.php -->
<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <h4 class="font-semibold text-gray-900">Informasi Produk</h4>
            <div class="mt-2 space-y-1 text-sm">
                <p><span class="font-medium">Nama:</span> {{ $record->product->name }}</p>
                <p><span class="font-medium">Kode:</span> {{ $record->product->code }}</p>
                <p><span class="font-medium">Kategori:</span> {{ $record->product->category->name }}</p>
            </div>
        </div>
        
        <div>
            <h4 class="font-semibold text-gray-900">Detail Pergerakan</h4>
            <div class="mt-2 space-y-1 text-sm">
                <p><span class="font-medium">Tanggal:</span> {{ $record->created_at->format('d/m/Y H:i') }}</p>
                <p><span class="font-medium">Tipe:</span> 
                    <span class="px-2 py-1 text-xs rounded-full
                        @if($record->type === 'in') bg-green-100 text-green-800
                        @elseif($record->type === 'out') bg-red-100 text-red-800
                        @else bg-yellow-100 text-yellow-800 @endif">
                        {{ $record->type === 'in' ? 'Stok Masuk' : ($record->type === 'out' ? 'Stok Keluar' : 'Adjustment') }}
                    </span>
                </p>
                <p><span class="font-medium">Sumber:</span> 
                    {{ $record->reference_type === 'purchase' ? 'Pembelian' : ($record->reference_type === 'sale' ? 'Penjualan' : 'Manual Adjustment') }}
                </p>
            </div>
        </div>
    </div>
    
    <div class="border-t pt-4">
        <h4 class="font-semibold text-gray-900">Perubahan Stok</h4>
        <div class="mt-2 flex items-center justify-between bg-gray-50 p-3 rounded">
            <div class="text-center">
                <p class="text-xs text-gray-500">Stok Sebelum</p>
                <p class="text-lg font-bold">{{ $record->previous_stock }}</p>
            </div>
            <div class="text-center">
                <p class="text-xs text-gray-500">Perubahan</p>
                <p class="text-lg font-bold 
                    @if($record->type === 'in') text-green-600
                    @elseif($record->type === 'out') text-red-600
                    @else text-yellow-600 @endif">
                    {{ $record->type === 'in' ? '+' : ($record->type === 'out' ? '-' : '') }}{{ $record->quantity }}
                </p>
            </div>
            <div class="text-center">
                <p class="text-xs text-gray-500">Stok Sesudah</p>
                <p class="text-lg font-bold text-blue-600">{{ $record->current_stock }}</p>
            </div>
        </div>
    </div>
    
    @if($record->notes)
    <div class="border-t pt-4">
        <h4 class="font-semibold text-gray-900">Catatan</h4>
        <p class="mt-2 text-sm text-gray-600 bg-gray-50 p-3 rounded">{{ $record->notes }}</p>
    </div>
    @endif
    
    @if($record->reference_id)
    <div class="border-t pt-4">
        <h4 class="font-semibold text-gray-900">Referensi Transaksi</h4>
        <div class="mt-2">
            @if($record->reference_type === 'purchase')
                @php $po = \App\Models\PurchaseOrder::find($record->reference_id); @endphp
                @if($po)
                    <div class="bg-blue-50 p-3 rounded">
                        <p class="font-medium text-blue-900">Purchase Order: {{ $po->po_number }}</p>
                        <p class="text-sm text-blue-700">Supplier: {{ $po->supplier->name }}</p>
                        <p class="text-sm text-blue-700">Tanggal: {{ $po->purchase_date->format('d/m/Y') }}</p>
                    </div>
                @endif
            @elseif($record->reference_type === 'sale')
                @php $so = \App\Models\SalesOrder::find($record->reference_id); @endphp
                @if($so)
                    <div class="bg-green-50 p-3 rounded">
                        <p class="font-medium text-green-900">Sales Order: {{ $so->so_number }}</p>
                        <p class="text-sm text-green-700">Customer: {{ $so->customer->name }}</p>
                        <p class="text-sm text-green-700">Tanggal: {{ $so->sale_date->format('d/m/Y') }}</p>
                    </div>
                @endif
            @endif
        </div>
    </div>
    @endif
</div>