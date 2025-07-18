<?php
// app/Models/SalesOrder.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class SalesOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'so_number',
        'customer_id',
        'sale_date',
        'total_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function calculateTotal(): void
    {
        $this->total_amount = $this->items->sum('total_price');
        $this->save();
    }

    public function updateProductStock(): void
    {
        if ($this->status === 'completed') {
            foreach ($this->items as $item) {
                $product = $item->product;
                $previousStock = $product->current_stock;
                
                // Cek stok cukup
                if ($product->current_stock < $item->quantity) {
                    throw new \Exception("Stok {$product->name} tidak cukup. Tersedia: {$product->current_stock}, Dibutuhkan: {$item->quantity}");
                }
                
                // Kurangi stok
                $product->current_stock -= $item->quantity;
                $product->save();

                // Create stock movement dengan keterangan yang jelas
                \App\Models\StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'out',
                    'reference_type' => 'sale',
                    'reference_id' => $this->id,
                    'quantity' => $item->quantity,
                    'previous_stock' => $previousStock,
                    'current_stock' => $product->current_stock,
                    'notes' => "Penjualan ke {$this->customer->name} - SO: {$this->so_number} - Produk: {$product->name}",
                ]);
            }
        }
    }

    // Method terpisah untuk handle item yang ditambah setelah SO completed
    public function updateStockForNewItems(): void
    {
        if ($this->status === 'completed') {
            foreach ($this->items as $item) {
                // Cek apakah item ini sudah diproses (sudah ada movement-nya)
                $existingMovement = \App\Models\StockMovement::where('reference_id', $this->id)
                    ->where('reference_type', 'sale')
                    ->where('product_id', $item->product_id)
                    ->exists();
                    
                if (!$existingMovement) {
                    // Process item yang belum ada movement-nya
                    $product = $item->product;
                    $previousStock = $product->current_stock;
                    
                    // Cek stok cukup
                    if ($product->current_stock < $item->quantity) {
                        throw new \Exception("Stok {$product->name} tidak cukup. Tersedia: {$product->current_stock}, Dibutuhkan: {$item->quantity}");
                    }
                    
                    // Kurangi stok
                    $product->current_stock -= $item->quantity;
                    $product->save();

                    // Create stock movement
                    \App\Models\StockMovement::create([
                        'product_id' => $product->id,
                        'type' => 'out',
                        'reference_type' => 'sale',
                        'reference_id' => $this->id,
                        'quantity' => $item->quantity,
                        'previous_stock' => $previousStock,
                        'current_stock' => $product->current_stock,
                        'notes' => "Item ditambah ke SO completed - SO: {$this->so_number} - Produk: {$product->name}",
                    ]);
                    
                    Log::info("Stock updated for new item: Product {$product->name}, Qty: {$item->quantity}, New Stock: {$product->current_stock}");
                }
            }
        }
        
        // Update total setelah ada item baru
        $this->calculateTotal();
    }

    // Method untuk rollback item yang dihapus dari SO completed
    public function rollbackStockForDeletedItem($deletedItem): void
    {
        if ($this->status === 'completed') {
            $product = $deletedItem->product;
            $previousStock = $product->current_stock;
            
            // Kembalikan stok
            $product->current_stock += $deletedItem->quantity;
            $product->save();

            // Create rollback movement
            \App\Models\StockMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'reference_type' => 'adjustment',
                'reference_id' => $this->id,
                'quantity' => $deletedItem->quantity,
                'previous_stock' => $previousStock,
                'current_stock' => $product->current_stock,
                'notes' => "Item dihapus dari SO completed - SO: {$this->so_number} - Produk: {$product->name}",
            ]);
            
            Log::info("Stock rolled back for deleted item: Product {$product->name}, Qty: {$deletedItem->quantity}, New Stock: {$product->current_stock}");
        }
        
        // Update total setelah item dihapus
        $this->calculateTotal();
    }
    
    // Method untuk rollback semua item saat SO dibatalkan/dihapus
    public function rollbackProductStock(): void
    {
        // Rollback stock saat sales order dibatalkan atau dihapus
        foreach ($this->items as $item) {
            $product = $item->product;
            $previousStock = $product->current_stock;
            
            // Kembalikan stok
            $product->current_stock += $item->quantity;
            $product->save();

            // Create stock movement untuk rollback
            \App\Models\StockMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'reference_type' => 'adjustment',
                'reference_id' => $this->id,
                'quantity' => $item->quantity,
                'previous_stock' => $previousStock,
                'current_stock' => $product->current_stock,
                'notes' => "Rollback penjualan (SO dihapus/dibatalkan) - SO: {$this->so_number} - Produk: {$product->name}",
            ]);
        }
    }

    protected static function boot()
    {
        parent::boot();

        // Saat sales order dihapus, rollback stok
        static::deleting(function ($salesOrder) {
            if ($salesOrder->status === 'completed') {
                $salesOrder->rollbackProductStock();
            }
        });

        // Saat status berubah
        static::updating(function ($salesOrder) {
            $originalStatus = $salesOrder->getOriginal('status');
            $newStatus = $salesOrder->status;

            // Jika dari completed ke cancelled, rollback stok
            if ($originalStatus === 'completed' && $newStatus === 'cancelled') {
                $salesOrder->rollbackProductStock();
            }
            
            // Jika dari cancelled/pending ke completed, update stok
            if (in_array($originalStatus, ['pending', 'cancelled']) && $newStatus === 'completed') {
                $salesOrder->updateProductStock();
            }
        });
    }
}