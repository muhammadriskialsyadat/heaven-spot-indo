<?php

use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportExportController;

// Redirect homepage ke admin login
Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/purchase-order/{purchaseOrder}/invoice', function (PurchaseOrder $purchaseOrder) {
    return view('filament.pages.purchase-invoice', compact('purchaseOrder'));
})->name('purchase-order.invoice')->middleware('auth');

Route::post('/reports/export/pdf', [ReportExportController::class, 'exportPdf'])->name('reports.export.pdf');
Route::post('/reports/export/excel', [ReportExportController::class, 'exportExcel'])->name('reports.export.excel');
