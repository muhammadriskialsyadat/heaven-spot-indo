<?php
// app/Filament/Widgets/StatsOverviewWidget.php
namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $lowStockCount = Product::whereColumn('current_stock', '<=', 'minimum_stock')->count();
        $outOfStockCount = Product::where('current_stock', '<=', 0)->count();
        $todayPurchases = PurchaseOrder::whereDate('created_at', today())->count();
        $todaySales = SalesOrder::whereDate('created_at', today())->count();

        return [
            Stat::make('Total Produk', Product::count())
                ->description('Jumlah produk')
                ->descriptionIcon('heroicon-m-cube')
                ->color('success'),
            
            Stat::make('Total Kategori', Category::count())
                ->description('Jumlah kategori')
                ->descriptionIcon('heroicon-m-tag')
                ->color('info'),
            
            Stat::make('Total Supplier', Supplier::count())
                ->description('Jumlah supplier')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('warning'),
            
            Stat::make('Total Pelanggan', Customer::count())
                ->description('Jumlah pelanggan')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
            
            Stat::make('Stok Menipis', $lowStockCount)
                ->description('Produk dengan stok menipis')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),
            
            Stat::make('Stok Habis', $outOfStockCount)
                ->description('Produk dengan stok habis')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
            
            Stat::make('Pembelian Hari Ini', $todayPurchases)
                ->description('Transaksi pembelian hari ini')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('success'),
            
            Stat::make('Penjualan Hari Ini', $todaySales)
                ->description('Transaksi penjualan hari ini')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
        ];
    }
}