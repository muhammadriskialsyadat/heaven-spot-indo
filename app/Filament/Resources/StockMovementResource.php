<?php
// app/Filament/Resources/StockMovementResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\StockMovementResource\Pages;
use App\Models\StockMovement;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Pergerakan Stok';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Manual Stock Adjustment')
                    ->description('Gunakan untuk adjustment stok manual (koreksi, rusak, hilang, dll)')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Produk')
                            ->relationship('product', 'name')
                            ->required()
                            ->searchable()
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} ({$record->code}) - Stok: {$record->current_stock}")
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $product = Product::find($state);
                                    $set('previous_stock', $product->current_stock);
                                }
                            }),
                        Forms\Components\TextInput::make('previous_stock')
                            ->label('Stok Saat Ini')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('adjustment_type')
                            ->label('Tipe Adjustment')
                            ->options([
                                'add' => 'Tambah Stok (+)',
                                'reduce' => 'Kurangi Stok (-)',
                                'correction' => 'Koreksi Stok',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Jumlah')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->helperText('Masukkan jumlah yang ingin ditambah/kurangi'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan/Alasan')
                            ->required()
                            ->placeholder('Contoh: Barang rusak, Stock opname, Koreksi data, dll')
                            ->rows(3),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal & Waktu')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => "Kode: {$record->product->code}"),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        'adjustment' => 'warning',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'in' => 'Stok Masuk',
                        'out' => 'Stok Keluar',
                        'adjustment' => 'Adjustment',
                    })
                    ->icon(fn ($state) => match ($state) {
                        'in' => 'heroicon-o-arrow-down-circle',
                        'out' => 'heroicon-o-arrow-up-circle',
                        'adjustment' => 'heroicon-o-adjustments-horizontal',
                    }),
                Tables\Columns\TextColumn::make('reference_type')
                    ->label('Sumber Transaksi')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'purchase' => 'Pembelian',
                        'sale' => 'Penjualan',
                        'adjustment' => 'Manual Adjustment',
                    })
                    ->description(fn ($record) => static::getReferenceDescription($record)),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($record) => match ($record->type) {
                        'in' => 'success',
                        'out' => 'danger',
                        'adjustment' => 'warning',
                    })
                    ->formatStateUsing(fn ($state, $record) => match ($record->type) {
                        'in' => "+{$state}",
                        'out' => "-{$state}",
                        'adjustment' => $state,
                    }),
                Tables\Columns\TextColumn::make('previous_stock')
                    ->label('Stok Sebelum')
                    ->numeric()
                    ->sortable()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Stok Sesudah')
                    ->numeric()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->notes)
                    ->wrap(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Produk')
                    ->relationship('product', 'name')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Pergerakan')
                    ->options([
                        'in' => 'Stok Masuk',
                        'out' => 'Stok Keluar',
                        'adjustment' => 'Adjustment',
                    ]),
                Tables\Filters\SelectFilter::make('reference_type')
                    ->label('Sumber Transaksi')
                    ->options([
                        'purchase' => 'Pembelian',
                        'sale' => 'Penjualan',
                        'adjustment' => 'Manual Adjustment',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->label('Rentang Tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Detail Pergerakan Stok'),
                // Hanya bisa delete adjustment manual
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->reference_type === 'adjustment'),
            ])
            ->bulkActions([
                // Tidak ada bulk action untuk safety
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s') // Auto refresh setiap 30 detik
            ->emptyStateHeading('Belum Ada Pergerakan Stok')
            ->emptyStateDescription('Pergerakan stok akan muncul otomatis saat ada transaksi pembelian/penjualan.')
            ->emptyStateIcon('heroicon-o-arrow-path');
    }

    protected static function getReferenceDescription($record): ?string
    {
        return match ($record->reference_type) {
            'purchase' => "PO: " . ($record->reference_id ? \App\Models\PurchaseOrder::find($record->reference_id)?->po_number ?? "#{$record->reference_id}" : 'N/A'),
            'sale' => "SO: " . ($record->reference_id ? \App\Models\SalesOrder::find($record->reference_id)?->so_number ?? "#{$record->reference_id}" : 'N/A'),
            'adjustment' => 'Manual oleh Admin',
        };
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockMovements::route('/'),
            'create' => Pages\CreateStockMovement::route('/create'),
            'view' => Pages\ViewStockMovement::route('/{record}'),
        ];
    }

    // Override canCreate untuk kontrol akses
    public static function canCreate(): bool
    {
        return true; // Hanya untuk manual adjustment
    }

    // Override navigation badge untuk menampilkan jumlah movement hari ini
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('created_at', today())->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}