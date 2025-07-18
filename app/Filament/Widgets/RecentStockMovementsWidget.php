<?php
// app/Filament/Widgets/RecentStockMovementsWidget.php
namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\StockMovement;

class RecentStockMovementsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Aktivitas Stok Terbaru';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StockMovement::query()
                    ->with(['product'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('H:i')
                    ->description(fn ($record) => $record->created_at->format('d/m/Y'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produk')
                    ->description(fn ($record) => "Kode: {$record->product->code}")
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        'adjustment' => 'warning',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'in' => 'Masuk',
                        'out' => 'Keluar',
                        'adjustment' => 'Adjust',
                    })
                    ->icon(fn ($state) => match ($state) {
                        'in' => 'heroicon-o-arrow-down-circle',
                        'out' => 'heroicon-o-arrow-up-circle',
                        'adjustment' => 'heroicon-o-adjustments-horizontal',
                    }),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->formatStateUsing(fn ($state, $record) => match ($record->type) {
                        'in' => "+{$state}",
                        'out' => "-{$state}",
                        'adjustment' => $state,
                    })
                    ->color(fn ($record) => match ($record->type) {
                        'in' => 'success',
                        'out' => 'danger',
                        'adjustment' => 'warning',
                    })
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Stok Saat Ini')
                    ->weight('bold')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('reference_type')
                    ->label('Sumber')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'purchase' => 'Pembelian',
                        'sale' => 'Penjualan',
                        'adjustment' => 'Manual',
                    })
                    ->color(fn ($state) => match ($state) {
                        'purchase' => 'info',
                        'sale' => 'warning',
                        'adjustment' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Keterangan')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->notes)
                    ->wrap(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn ($record) => "Detail Pergerakan Stok - {$record->product->name}")
                    ->modalContent(fn ($record) => view('filament.widgets.stock-movement-detail', ['record' => $record]))
                    ->modalFooterActions([]),
            ])
            ->paginated(false)
            ->poll('30s') // Auto refresh setiap 30 detik
            ->emptyStateHeading('Belum Ada Pergerakan Stok')
            ->emptyStateDescription('Pergerakan stok akan muncul otomatis saat ada transaksi.')
            ->emptyStateIcon('heroicon-o-arrow-path');
    }
}