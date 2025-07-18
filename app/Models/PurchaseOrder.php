<?php
// app/Models/PurchaseOrder.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'purchase_date',
        'subtotal',
        'tax_percentage',
        'tax_amount',
        'discount_percentage',
        'discount_amount',
        'total_amount',
        'grand_total',
        'status',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-calculate saat creating PO baru
        static::creating(function ($po) {
            $po->subtotal = $po->subtotal ?? 0;
            $po->tax_percentage = $po->tax_percentage ?? 11;
            $po->discount_percentage = $po->discount_percentage ?? 0;
            $po->discount_amount = $po->discount_amount ?? 0;
            $po->tax_amount = $po->tax_amount ?? 0;
            $po->grand_total = $po->grand_total ?? 0;
            $po->total_amount = $po->grand_total;
        });

        // Saat purchase order dihapus, rollback stok
        static::deleting(function ($purchaseOrder) {
            if ($purchaseOrder->status === 'completed') {
                $purchaseOrder->rollbackProductStock();
            }
        });

        // Saat status berubah
        static::updating(function ($purchaseOrder) {
            $originalStatus = $purchaseOrder->getOriginal('status');
            $newStatus = $purchaseOrder->status;

            // Jika dari completed ke cancelled, rollback stok
            if ($originalStatus === 'completed' && $newStatus === 'cancelled') {
                $purchaseOrder->rollbackProductStock();
            }
            
            // Jika dari cancelled/pending ke completed, update stok
            if (in_array($originalStatus, ['pending', 'cancelled']) && $newStatus === 'completed') {
                $purchaseOrder->updateProductStock();
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function calculateTotal(): void
    {
        // Hitung subtotal dari items
        $this->subtotal = $this->items->sum('total_price');
        
        // Pastikan nilai default tidak null
        $this->tax_percentage = $this->tax_percentage ?? 11;
        $this->discount_percentage = $this->discount_percentage ?? 0;
        
        // Hitung diskon
        $this->discount_amount = 0;
        if ($this->discount_percentage > 0) {
            $this->discount_amount = $this->subtotal * ($this->discount_percentage / 100);
        }
        
        // Hitung total setelah diskon
        $totalAfterDiscount = $this->subtotal - $this->discount_amount;
        
        // Hitung pajak
        $this->tax_amount = 0;
        if ($this->tax_percentage > 0) {
            $this->tax_amount = $totalAfterDiscount * ($this->tax_percentage / 100);
        }
        
        // Hitung grand total
        $this->grand_total = $totalAfterDiscount + $this->tax_amount;
        
        // Update total_amount untuk kompatibilitas
        $this->total_amount = $this->grand_total;
        
        $this->save();
    }

    public function updateProductStock(): void
    {
        if ($this->status === 'completed') {
            foreach ($this->items as $item) {
                $product = $item->product;
                $previousStock = $product->current_stock;
                
                // Tambah stok (pembelian = stok naik)
                $product->current_stock += $item->quantity;
                $product->save();

                // Create stock movement dengan keterangan yang jelas
                \App\Models\StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'in',
                    'reference_type' => 'purchase',
                    'reference_id' => $this->id,
                    'quantity' => $item->quantity,
                    'previous_stock' => $previousStock,
                    'current_stock' => $product->current_stock,
                    'notes' => "Pembelian dari {$this->supplier->name} - PO: {$this->po_number} - Produk: {$product->name}",
                ]);
            }
        }
    }

    // Method terpisah untuk handle item yang ditambah setelah PO completed
    public function updateStockForNewItems(): void
    {
        if ($this->status === 'completed') {
            foreach ($this->items as $item) {
                // Cek apakah item ini sudah diproses (sudah ada movement-nya)
                $existingMovement = \App\Models\StockMovement::where('reference_id', $this->id)
                    ->where('reference_type', 'purchase')
                    ->where('product_id', $item->product_id)
                    ->exists();
                    
                if (!$existingMovement) {
                    // Process item yang belum ada movement-nya
                    $product = $item->product;
                    $previousStock = $product->current_stock;
                    
                    // Tambah stok (pembelian = stok naik)
                    $product->current_stock += $item->quantity;
                    $product->save();

                    // Create stock movement
                    \App\Models\StockMovement::create([
                        'product_id' => $product->id,
                        'type' => 'in',
                        'reference_type' => 'purchase',
                        'reference_id' => $this->id,
                        'quantity' => $item->quantity,
                        'previous_stock' => $previousStock,
                        'current_stock' => $product->current_stock,
                        'notes' => "Item ditambah ke PO completed - PO: {$this->po_number} - Produk: {$product->name}",
                    ]);
                    
                    Log::info("Stock updated for new purchase item: Product {$product->name}, Qty: {$item->quantity}, New Stock: {$product->current_stock}");
                }
            }
        }
        
        // Update total setelah ada item baru
        $this->calculateTotal();
    }

    // Method untuk rollback item yang dihapus dari PO completed
    public function rollbackStockForDeletedItem($deletedItem): void
    {
        if ($this->status === 'completed') {
            $product = $deletedItem->product;
            $previousStock = $product->current_stock;
            
            // Kurangi stok (rollback pembelian)
            $product->current_stock -= $deletedItem->quantity;
            $product->save();

            // Create rollback movement
            \App\Models\StockMovement::create([
                'product_id' => $product->id,
                'type' => 'out',
                'reference_type' => 'adjustment',
                'reference_id' => $this->id,
                'quantity' => $deletedItem->quantity,
                'previous_stock' => $previousStock,
                'current_stock' => $product->current_stock,
                'notes' => "Item dihapus dari PO completed - PO: {$this->po_number} - Produk: {$product->name}",
            ]);
            
            Log::info("Stock rolled back for deleted purchase item: Product {$product->name}, Qty: {$deletedItem->quantity}, New Stock: {$product->current_stock}");
        }
        
        // Update total setelah item dihapus
        $this->calculateTotal();
    }

    // Method untuk rollback semua item saat PO dibatalkan/dihapus
    public function rollbackProductStock(): void
    {
        foreach ($this->items as $item) {
            $product = $item->product;
            $previousStock = $product->current_stock;
            
            // Kurangi stok (rollback pembelian)
            $product->current_stock -= $item->quantity;
            $product->save();

            \App\Models\StockMovement::create([
                'product_id' => $product->id,
                'type' => 'out',
                'reference_type' => 'adjustment',
                'reference_id' => $this->id,
                'quantity' => $item->quantity,
                'previous_stock' => $previousStock,
                'current_stock' => $product->current_stock,
                'notes' => "Rollback pembelian (PO dihapus/dibatalkan) - PO: {$this->po_number} - Produk: {$product->name}",
            ]);
        }
    }
}