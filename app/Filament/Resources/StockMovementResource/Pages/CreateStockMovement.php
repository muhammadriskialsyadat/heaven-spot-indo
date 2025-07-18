<?php
// app/Filament/Resources/StockMovementResource/Pages/CreateStockMovement.php
namespace App\Filament\Resources\StockMovementResource\Pages;

use App\Filament\Resources\StockMovementResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Product;

class CreateStockMovement extends CreateRecord
{
    protected static string $resource = StockMovementResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Stock adjustment berhasil dibuat';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $product = Product::find($data['product_id']);
        $previousStock = $product->current_stock;
        
        // Hitung stok baru berdasarkan tipe adjustment
        $newStock = match ($data['adjustment_type']) {
            'add' => $previousStock + $data['quantity'],
            'reduce' => max(0, $previousStock - $data['quantity']), // Tidak boleh minus
            'correction' => $data['quantity'], // Set langsung ke nilai tertentu
        };
        
        // Update stok produk
        $product->update(['current_stock' => $newStock]);
        
        // Tentukan type berdasarkan perubahan stok
        $type = $newStock > $previousStock ? 'in' : ($newStock < $previousStock ? 'out' : 'adjustment');
        
        // Hitung quantity yang sebenarnya berubah
        $actualQuantity = abs($newStock - $previousStock);
        
        // Prepare data untuk stock movement
        return [
            'product_id' => $data['product_id'],
            'type' => $type,
            'reference_type' => 'adjustment',
            'reference_id' => null,
            'quantity' => $actualQuantity,
            'previous_stock' => $previousStock,
            'current_stock' => $newStock,
            'notes' => $data['notes'] . " (Manual adjustment: {$data['adjustment_type']})",
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}