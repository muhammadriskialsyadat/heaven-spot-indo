<?php
// app/Filament/Resources/PurchaseOrderResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Colors\Color;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Pembelian';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pembelian')
                    ->schema([
                        Forms\Components\TextInput::make('po_number')
                            ->label('No. Purchase Order')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'PO-' . date('Ymd') . '-' . str_pad(PurchaseOrder::whereDate('created_at', today())->count() + 1, 3, '0', STR_PAD_LEFT))
                            ->maxLength(100),
                        Forms\Components\Select::make('supplier_id')
                            ->label('Supplier')
                            ->relationship('supplier', 'name')
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Supplier')
                                    ->required(),
                                Forms\Components\TextInput::make('contact_person')
                                    ->label('Kontak Person'),
                                Forms\Components\TextInput::make('phone')
                                    ->label('No. Telepon')
                                    ->tel(),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email(),
                                Forms\Components\Textarea::make('address')
                                    ->label('Alamat'),
                            ]),
                        Forms\Components\DatePicker::make('purchase_date')
                            ->label('Tanggal Pembelian')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3),
                    ])->columns(2),
                    Forms\Components\Section::make('Pengaturan Pajak & Diskon')
                ->schema([
                    Forms\Components\TextInput::make('discount_percentage')
                        ->label('Diskon (%)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $set, $get) {
                            $subtotal = $get('subtotal') ?? 0;
                            $discountAmount = $subtotal * ($state / 100);
                            $set('discount_amount', $discountAmount);
                            
                            // Recalculate tax and grand total
                            $totalAfterDiscount = $subtotal - $discountAmount;
                            $taxPercentage = $get('tax_percentage') ?? 0;
                            $taxAmount = $totalAfterDiscount * ($taxPercentage / 100);
                            $set('tax_amount', $taxAmount);
                            $set('grand_total', $totalAfterDiscount + $taxAmount);
                        }),
                    
                    Forms\Components\TextInput::make('discount_amount')
                        ->label('Jumlah Diskon')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0)
                        ->disabled()
                        ->dehydrated(true),
                    
                    Forms\Components\Select::make('tax_percentage')
                        ->label('Pajak')
                        ->options([
                            0 => 'Tidak ada pajak (0%)',
                            10 => 'PPN 10%',
                            11 => 'PPN 11%',
                            12 => 'PPN 12%',
                        ])
                        ->default(11)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $set, $get) {
                            $subtotal = $get('subtotal') ?? 0;
                            $discountAmount = $get('discount_amount') ?? 0;
                            $totalAfterDiscount = $subtotal - $discountAmount;
                            $taxAmount = $totalAfterDiscount * ($state / 100);
                            $set('tax_amount', $taxAmount);
                            $set('grand_total', $totalAfterDiscount + $taxAmount);
                        }),
                    
                    Forms\Components\TextInput::make('tax_amount')
                        ->label('Jumlah Pajak')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0)
                        ->disabled()
                        ->dehydrated(true),
                ])->columns(2),
            
            Forms\Components\Section::make('Total Pembelian')
                ->schema([
                    Forms\Components\TextInput::make('subtotal')
                        ->label('Subtotal')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0)
                        ->disabled()
                        ->dehydrated(true),
                    
                    Forms\Components\TextInput::make('grand_total')
                        ->label('Grand Total')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0)
                        ->disabled()
                        ->dehydrated(true),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('po_number')
                ->label('No. PO')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('supplier.name')
                ->label('Supplier')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('purchase_date')
                ->label('Tanggal')
                ->date()
                ->sortable(),
            Tables\Columns\TextColumn::make('subtotal')
                ->label('Subtotal')
                ->money('IDR')
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('tax_percentage')
                ->label('Pajak (%)')
                ->suffix('%')
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('grand_total')
                ->label('Grand Total')
                ->money('IDR')
                ->sortable(),
            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn ($state) => match ($state) {
                    'pending' => 'warning',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                })
                ->formatStateUsing(fn ($state) => match ($state) {
                    'pending' => 'Pending',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                }),
        ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\Filter::make('purchase_date')
                    ->label('Tanggal Pembelian')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('purchase_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('purchase_date', '<=', $data['until']));
                    }),
            ])
             ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('print_invoice')
                ->label('Print Invoice')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn ($record) => route('purchase-order.invoice', $record))
                ->openUrlInNewTab()
                ->visible(fn ($record) => $record->status === 'completed'),
            Tables\Actions\Action::make('complete')
                ->label('Selesaikan')
                ->icon('heroicon-o-check')
                ->color('success')
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->status = 'completed';
                    $record->save();
                    $record->updateProductStock();
                })
                ->visible(fn ($record) => $record->status === 'pending'),
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
            RelationManagers\ItemsRelationManager::class,
            
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'view' => Pages\ViewPurchaseOrder::route('/{record}'),
            
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}