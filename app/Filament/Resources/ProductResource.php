<?php
// app/Filament/Resources/ProductResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Colors\Color;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Produk';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Produk')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Produk')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Produk')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),
                        Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Kategori')
                                    ->required(),
                                Forms\Components\Textarea::make('description')
                                    ->label('Deskripsi'),
                            ]),
                        Forms\Components\TextInput::make('brand')
                            ->label('Brand')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('color')
                            ->label('Warna')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('size')
                            ->label('Ukuran')
                            ->placeholder('Contoh: 1L, 2.5L, 5L')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('unit')
                            ->label('Satuan')
                            ->placeholder('Contoh: Kaleng, Galon, Liter')
                            ->required()
                            ->maxLength(20),
                    ])->columns(2),
                
                Forms\Components\Section::make('Stok & Harga')
                    ->schema([
                        Forms\Components\TextInput::make('minimum_stock')
                            ->label('Stok Minimum')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        Forms\Components\TextInput::make('current_stock')
                            ->label('Stok Saat Ini')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        Forms\Components\TextInput::make('purchase_price')
                            ->label('Harga Beli')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->minValue(0),
                        Forms\Components\TextInput::make('selling_price')
                            ->label('Harga Jual')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->minValue(0),
                    ])->columns(2),
                
                Forms\Components\Section::make('Deskripsi')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand')
                    ->label('Brand')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('color')
                    ->label('Warna')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('size')
                    ->label('Ukuran')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Stok')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state, $record) => match ($record->stock_status) {
                        'out' => 'danger',
                        'low' => 'warning',
                        'normal' => 'success',
                    }),
                Tables\Columns\TextColumn::make('minimum_stock')
                    ->label('Min. Stok')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('stock_status')
                    ->label('Status Stok')
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
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Harga Beli')
                    ->money('IDR')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('stock_status')
                    ->label('Status Stok')
                    ->options([
                        'out' => 'Habis',
                        'low' => 'Menipis',
                        'normal' => 'Normal',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}