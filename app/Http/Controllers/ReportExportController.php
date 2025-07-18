<?php
// app/Http/Controllers/ReportExportController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExportController extends Controller
{
    public function exportPdf(Request $request)
    {
        $reportType = $request->input('report_type');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $reportData = $this->generateReportData($reportType, $startDate, $endDate);
        
        // Configure DomPDF
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        // Generate HTML for PDF
        $html = view('reports.pdf-template', [
            'reportData' => $reportData,
            'reportType' => $reportType,
            'startDate' => $startDate,
            'endDate' => $endDate
        ])->render();
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $filename = $this->getFilename($reportType, 'pdf');
        
        return $dompdf->stream($filename);
    }
    
    public function exportExcel(Request $request)
    {
        $reportType = $request->input('report_type');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $reportData = $this->generateReportData($reportType, $startDate, $endDate);
        
        $filename = $this->getFilename($reportType, 'xlsx');
        
        return Excel::download(
            new ReportExport($reportData, $reportType),
            $filename
        );
    }
    
    private function generateReportData($reportType, $startDate, $endDate)
    {
        return match ($reportType) {
            'stock' => $this->generateStockReport(),
            'sales' => $this->generateSalesReport($startDate, $endDate),
            'purchase' => $this->generatePurchaseReport($startDate, $endDate),
            'stock_movement' => $this->generateStockMovementReport($startDate, $endDate),
            default => null,
        };
    }
    
    private function generateStockReport()
    {
        $products = Product::with('category')->orderBy('name')->get();
        
        return [
            'type' => 'stock',
            'products' => $products,
            'total_products' => $products->count(),
            'low_stock_products' => $products->filter(fn($p) => $p->current_stock <= $p->minimum_stock),
            'out_of_stock_products' => $products->filter(fn($p) => $p->current_stock <= 0),
        ];
    }
    
    private function generateSalesReport($startDate, $endDate)
    {
        $start = Carbon::parse($startDate)->format('Y-m-d');
        $end = Carbon::parse($endDate)->format('Y-m-d');
        
        $sales = SalesOrder::with(['customer', 'items.product'])
            ->whereDate('sale_date', '>=', $start)
            ->whereDate('sale_date', '<=', $end)
            ->where('status', 'completed')
            ->orderBy('sale_date', 'desc')
            ->get();
            
        return [
            'type' => 'sales',
            'sales' => $sales,
            'total_sales' => $sales->count(),
            'total_amount' => $sales->sum('total_amount'),
            'period' => ['start' => $startDate, 'end' => $endDate],
        ];
    }
    
    private function generatePurchaseReport($startDate, $endDate)
    {
        $start = Carbon::parse($startDate)->format('Y-m-d');
        $end = Carbon::parse($endDate)->format('Y-m-d');
        
        $purchases = PurchaseOrder::with(['supplier', 'items.product'])
            ->whereDate('purchase_date', '>=', $start)
            ->whereDate('purchase_date', '<=', $end)
            ->where('status', 'completed')
            ->orderBy('purchase_date', 'desc')
            ->get();
            
        return [
            'type' => 'purchase',
            'purchases' => $purchases,
            'total_purchases' => $purchases->count(),
            'total_amount' => $purchases->sum('total_amount'),
            'period' => ['start' => $startDate, 'end' => $endDate],
        ];
    }
    
    private function generateStockMovementReport($startDate, $endDate)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        
        $movements = StockMovement::with('product')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return [
            'type' => 'stock_movement',
            'movements' => $movements,
            'total_movements' => $movements->count(),
            'total_in' => $movements->where('type', 'in')->sum('quantity'),
            'total_out' => $movements->where('type', 'out')->sum('quantity'),
            'period' => ['start' => $startDate, 'end' => $endDate],
        ];
    }
    
    private function getFilename($reportType, $extension)
    {
        $typeNames = [
            'stock' => 'Laporan_Stok',
            'sales' => 'Laporan_Penjualan', 
            'purchase' => 'Laporan_Pembelian',
            'stock_movement' => 'Laporan_Pergerakan_Stok'
        ];
        
        $typeName = $typeNames[$reportType] ?? 'Laporan';
        $date = now()->format('Y-m-d_H-i');
        
        return "{$typeName}_{$date}.{$extension}";
    }
}

// Excel Export Class
class ReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $reportData;
    protected $reportType;
    
    public function __construct($reportData, $reportType)
    {
        $this->reportData = $reportData;
        $this->reportType = $reportType;
    }
    
    public function collection()
    {
        return match ($this->reportType) {
            'stock' => $this->reportData['products'],
            'sales' => $this->reportData['sales'],
            'purchase' => $this->reportData['purchases'],
            'stock_movement' => $this->reportData['movements'],
            default => collect([]),
        };
    }
    
    public function headings(): array
    {
        return match ($this->reportType) {
            'stock' => ['Kode', 'Nama Produk', 'Kategori', 'Stok Saat Ini', 'Stok Minimum', 'Status'],
            'sales' => ['No. SO', 'Customer', 'Tanggal', 'Total Amount', 'Status'],
            'purchase' => ['No. PO', 'Supplier', 'Tanggal', 'Total Amount', 'Status'],
            'stock_movement' => ['Tanggal', 'Produk', 'Tipe', 'Quantity', 'Stok Sebelum', 'Stok Sesudah', 'Keterangan'],
            default => [],
        };
    }
    
    public function map($row): array
    {
        return match ($this->reportType) {
            'stock' => [
                $row->code,
                $row->name,
                $row->category->name,
                $row->current_stock,
                $row->minimum_stock,
                $row->current_stock <= 0 ? 'Habis' : ($row->current_stock <= $row->minimum_stock ? 'Menipis' : 'Normal')
            ],
            'sales' => [
                $row->so_number,
                $row->customer->name,
                $row->sale_date->format('d/m/Y'),
                $row->total_amount,
                ucfirst($row->status)
            ],
            'purchase' => [
                $row->po_number,
                $row->supplier->name,
                $row->purchase_date->format('d/m/Y'),
                $row->total_amount,
                ucfirst($row->status)
            ],
            'stock_movement' => [
                $row->created_at->format('d/m/Y H:i'),
                $row->product->name,
                $row->type === 'in' ? 'Masuk' : 'Keluar',
                $row->quantity,
                $row->previous_stock,
                $row->current_stock,
                $row->notes
            ],
            default => [],
        };
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}