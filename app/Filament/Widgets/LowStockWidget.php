<?php
// app/Filament/Widgets/LowStockWidget.php
namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Product;

class LowStockWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Produk dengan Stok Menipis';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->whereColumn('current_stock', '<=', 'minimum_stock')
                    ->orWhere('current_stock', '<=', 0)
            )
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori'),
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Stok Saat Ini')
                    ->badge()
                    ->color(fn ($record) => $record->current_stock <= 0 ? 'danger' : 'warning'),
                Tables\Columns\TextColumn::make('minimum_stock')
                    ->label('Stok Minimum'),
                Tables\Columns\TextColumn::make('stock_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'out' => 'danger',
                        'low' => 'warning',
                        'normal' => 'success',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'out' => 'Habis',
                        'low' => 'Menipis',
                        'normal' => 'Normal',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('restock')
                    ->label('Restock')
                    ->icon('heroicon-o-plus')
                    ->url(fn ($record) => route('filament.admin.resources.products.edit', $record))
                    ->openUrlInNewTab(),
            ]);
    }
}