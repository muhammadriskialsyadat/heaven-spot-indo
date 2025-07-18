<?php
// app/Filament/Resources/SalesOrderResource/RelationManagers/ItemsRelationManager.php
namespace App\Filament\Resources\SalesOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Product;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Item Penjualan';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Produk')
                    ->relationship('product', 'name')
                    ->required()
                    ->searchable()
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} ({$record->code}) - Stok: {$record->current_stock}")
                    ->live() // UBAH dari reactive() ke live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $product = Product::find($state);
                            if ($product) {
                                $set('unit_price', $product->selling_price);
                                $set('available_stock', $product->current_stock);
                                
                                // Debug log
                                Log::info("Product selected: {$product->name}, Stock: {$product->current_stock}");
                            }
                        }
                    }),
                    
                Forms\Components\Hidden::make('available_stock')
                    ->default(0),
                    
                Forms\Components\TextInput::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->live() // UBAH dari reactive() ke live()
                    ->rules([
                        function (Get $get) {
                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                $availableStock = (int) $get('available_stock');
                                if ($value > $availableStock) {
                                    $fail("Jumlah tidak boleh melebihi stok yang tersedia ({$availableStock})");
                                }
                            };
                        },
                    ])
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        $unitPrice = $get('unit_price');
                        if ($state && $unitPrice) {
                            $set('total_price', $state * $unitPrice);
                        }
                    })
                    ->helperText(function (Get $get) {
                        $stock = $get('available_stock');
                        return $stock ? "Stok tersedia: {$stock}" : '';
                    }),
                    
                Forms\Components\TextInput::make('unit_price')
                    ->label('Harga Satuan')
                    ->numeric()
                    ->prefix('Rp')
                    ->required()
                    ->minValue(0)
                    ->live() // UBAH dari reactive() ke live()
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        $quantity = $get('quantity');
                        if ($state && $quantity) {
                            $set('total_price', $state * $quantity);
                        }
                    }),
                    
                Forms\Components\TextInput::make('total_price')
                    ->label('Total Harga')
                    ->numeric()
                    ->prefix('Rp')
                    ->required()
                    ->minValue(0)
                    ->disabled()
                    ->dehydrated(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.name')
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produk'),
                Tables\Columns\TextColumn::make('product.code')
                    ->label('Kode'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Jumlah'),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Harga Satuan')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->money('IDR'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Pastikan total_price dihitung
                        $data['total_price'] = $data['quantity'] * $data['unit_price'];
                        
                        // Debug log
                        Log::info("Creating item with data: " . json_encode($data));
                        
                        return $data;
                    })
                    ->after(function ($record) {
                        try {
                            // Debug log
                            Log::info("Item created with ID: {$record->id}, SO Status: {$this->ownerRecord->status}");
                            
                            // AUTO-CALL method untuk handle SO completed
                            if ($this->ownerRecord->status === 'completed') {
                                Log::info("Calling updateStockForNewItems for SO: {$this->ownerRecord->so_number}");
                                $this->ownerRecord->updateStockForNewItems();
                                
                                // Notification sukses
                                Notification::make()
                                    ->title('Item berhasil ditambahkan')
                                    ->body('Stok produk telah dikurangi otomatis karena SO sudah completed')
                                    ->success()
                                    ->send();
                            } else {
                                // Hanya update total jika SO belum completed
                                $this->ownerRecord->calculateTotal();
                                
                                Notification::make()
                                    ->title('Item berhasil ditambahkan')
                                    ->body('Item ditambahkan ke SO. Stok akan dikurangi saat SO diselesaikan.')
                                    ->info()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            // Debug log error
                            Log::error("Error in after() hook: " . $e->getMessage());
                            
                            // Notification error
                            Notification::make()
                                ->title('Error menambah item')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['total_price'] = $data['quantity'] * $data['unit_price'];
                        return $data;
                    })
                    ->after(function () {
                        $this->ownerRecord->calculateTotal();
                        
                        Notification::make()
                            ->title('Item berhasil diperbarui')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->before(function ($record) {
                        try {
                            if ($this->ownerRecord->status === 'completed') {
                                Log::info("Rolling back stock for deleted item: {$record->id}");
                                $this->ownerRecord->rollbackStockForDeletedItem($record);
                                
                                Notification::make()
                                    ->title('Stok dikembalikan')
                                    ->body("Stok {$record->product->name} telah dikembalikan sebanyak {$record->quantity} unit")
                                    ->info()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Log::error("Error rolling back stock: " . $e->getMessage());
                            
                            Notification::make()
                                ->title('Error menghapus item')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                                
                            $this->halt();
                        }
                    })
                    ->after(function () {
                        $this->ownerRecord->calculateTotal();
                    }),
            ])
            ->emptyStateHeading('Belum Ada Item')
            ->emptyStateDescription('Tambahkan item produk untuk sales order ini.')
            ->emptyStateIcon('heroicon-o-cube');
    }
}