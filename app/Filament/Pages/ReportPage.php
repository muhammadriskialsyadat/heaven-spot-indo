<?php
// app/Filament/Pages/ReportPage.php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use Illuminate\Support\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ReportPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string $view = 'filament.pages.report-page';

    protected static ?string $navigationLabel = 'Laporan';

    protected static ?string $navigationGroup = 'Laporan';

    public ?array $data = [];
    
    public $reportData = null;

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'report_type' => 'stock',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('report_type')
                    ->label('Jenis Laporan')
                    ->options([
                        'stock' => 'Laporan Stok',
                        'purchase' => 'Laporan Pembelian',
                        'sales' => 'Laporan Penjualan',
                        'stock_movement' => 'Laporan Pergerakan Stok',
                    ])
                    ->required()
                    ->live() // Add live updating
                    ->afterStateUpdated(function () {
                        $this->reportData = null; // Reset report data when type changes
                    }),
                DatePicker::make('start_date')
                    ->label('Tanggal Mulai')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function () {
                        $this->reportData = null;
                    }),
                DatePicker::make('end_date')
                    ->label('Tanggal Akhir')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function () {
                        $this->reportData = null;
                    }),
            ])
            ->columns(3)
            ->statePath('data');
    }

    public function generateReport()
    {
        $data = $this->form->getState();
        
        // Debug logging
        Log::info('Generating report', [
            'type' => $data['report_type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date']
        ]);
        
        try {
            $this->reportData = match ($data['report_type']) {
                'stock' => $this->generateStockReport(),
                'purchase' => $this->generatePurchaseReport($data['start_date'], $data['end_date']),
                'sales' => $this->generateSalesReport($data['start_date'], $data['end_date']),
                'stock_movement' => $this->generateStockMovementReport($data['start_date'], $data['end_date']),
                default => null,
            };

            // Debug hasil
            Log::info('Report generated', [
                'type' => $data['report_type'],
                'data_count' => $this->getDataCount($this->reportData)
            ]);

            // Show notification
            if ($this->reportData) {
                $count = $this->getDataCount($this->reportData);
                Notification::make()
                    ->title('Laporan berhasil dibuat')
                    ->body("Ditemukan {$count} data")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Tidak ada data')
                    ->body('Tidak ditemukan data pada periode yang dipilih')
                    ->warning()
                    ->send();
            }

        } catch (\Exception $e) {
            Log::error('Report generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Notification::make()
                ->title('Error generating report')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        return $this->reportData;
    }

    private function getDataCount($reportData): int
    {
        if (!$reportData) return 0;
        
        return match ($reportData['type']) {
            'stock' => $reportData['total_products'] ?? 0,
            'purchase' => $reportData['total_purchases'] ?? 0,
            'sales' => $reportData['total_sales'] ?? 0,
            'stock_movement' => $reportData['total_movements'] ?? 0,
            default => 0,
        };
    }

    private function generateStockReport()
    {
        $products = Product::with('category')
            ->orderBy('name')
            ->get();

        $lowStockProducts = $products->filter(fn($product) => $product->current_stock <= $product->minimum_stock);
        $outOfStockProducts = $products->filter(fn($product) => $product->current_stock <= 0);

        Log::info('Stock report generated', [
            'total_products' => $products->count(),
            'low_stock' => $lowStockProducts->count(),
            'out_of_stock' => $outOfStockProducts->count()
        ]);

        return [
            'type' => 'stock',
            'products' => $products,
            'low_stock_products' => $lowStockProducts,
            'out_of_stock_products' => $outOfStockProducts,
            'total_products' => $products->count(),
            'total_stock_value' => $products->sum(fn($product) => $product->current_stock * $product->purchase_price),
        ];
    }

    private function generatePurchaseReport($startDate, $endDate)
    {
        // Convert strings to Carbon instances - use DATE ONLY for purchase_date comparison
        $start = Carbon::parse($startDate)->format('Y-m-d');
        $end = Carbon::parse($endDate)->format('Y-m-d');
        
        Log::info('Purchase report query', [
            'start_date' => $start,
            'end_date' => $end
        ]);

        $purchases = PurchaseOrder::with(['supplier', 'items.product'])
            ->whereDate('purchase_date', '>=', $start)
            ->whereDate('purchase_date', '<=', $end)
            ->where('status', 'completed')
            ->orderBy('purchase_date', 'desc')
            ->get();

        Log::info('Purchase report result', [
            'total_purchases' => $purchases->count(),
            'total_amount' => $purchases->sum('total_amount')
        ]);

        return [
            'type' => 'purchase',
            'purchases' => $purchases,
            'total_purchases' => $purchases->count(),
            'total_amount' => $purchases->sum('total_amount'),
            'period' => ['start' => $startDate, 'end' => $endDate],
        ];
    }

    private function generateSalesReport($startDate, $endDate)
    {
        // Convert strings to Carbon instances - use DATE ONLY for sale_date comparison
        $start = Carbon::parse($startDate)->format('Y-m-d');
        $end = Carbon::parse($endDate)->format('Y-m-d');
        
        Log::info('Sales report query', [
            'start_date' => $start,
            'end_date' => $end
        ]);

        $sales = SalesOrder::with(['customer', 'items.product'])
            ->whereDate('sale_date', '>=', $start)
            ->whereDate('sale_date', '<=', $end)
            ->where('status', 'completed')
            ->orderBy('sale_date', 'desc')
            ->get();

        Log::info('Sales report result', [
            'total_sales' => $sales->count(),
            'total_amount' => $sales->sum('total_amount'),
            'sales_data' => $sales->map(fn($s) => [
                'so_number' => $s->so_number,
                'customer' => $s->customer->name ?? 'N/A',
                'date' => $s->sale_date->format('Y-m-d'),
                'amount' => $s->total_amount
            ])
        ]);

        return [
            'type' => 'sales',
            'sales' => $sales,
            'total_sales' => $sales->count(),
            'total_amount' => $sales->sum('total_amount'),
            'period' => ['start' => $startDate, 'end' => $endDate],
        ];
    }

    private function generateStockMovementReport($startDate, $endDate)
    {
        // Convert strings to Carbon instances - use DATETIME for created_at comparison
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        
        Log::info('Stock movement report query', [
            'start_date' => $start->format('Y-m-d H:i:s'),
            'end_date' => $end->format('Y-m-d H:i:s')
        ]);

        $movements = StockMovement::with('product')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->orderBy('created_at', 'desc')
            ->get();

        Log::info('Stock movement report result', [
            'total_movements' => $movements->count(),
            'total_in' => $movements->where('type', 'in')->sum('quantity'),
            'total_out' => $movements->where('type', 'out')->sum('quantity'),
            'movements_sample' => $movements->take(3)->map(fn($m) => [
                'id' => $m->id,
                'product' => $m->product->name ?? 'N/A',
                'type' => $m->type,
                'quantity' => $m->quantity,
                'created_at' => $m->created_at->format('Y-m-d H:i:s')
            ])
        ]);

        return [
            'type' => 'stock_movement',
            'movements' => $movements,
            'total_movements' => $movements->count(),
            'total_in' => $movements->where('type', 'in')->sum('quantity'),
            'total_out' => $movements->where('type', 'out')->sum('quantity'),
            'period' => ['start' => $startDate, 'end' => $endDate],
        ];
    }
}