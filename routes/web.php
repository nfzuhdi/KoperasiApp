<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/app');
});

Route::get('/loan-payment/invoice/{record}', [\App\Http\Controllers\LoanPaymentInvoiceController::class, 'generateInvoice'])
    ->name('loan-payment.invoice')
    ->middleware(['auth']);

Route::get('/buku-besar/export-pdf', [\App\Http\Controllers\BukuBesarController::class, 'exportPdf'])
    ->name('buku-besar.export-pdf')
    ->middleware(['auth']);

Route::get('/neraca-saldo/export-pdf', [\App\Http\Controllers\NeracaSaldoController::class, 'exportPdf'])
    ->name('neraca-saldo.export-pdf')
    ->middleware(['auth']);

Route::get('/neraca-saldo/export-excel', [\App\Http\Controllers\NeracaSaldoController::class, 'exportExcel'])
    ->name('neraca-saldo.export-excel')
    ->middleware(['auth']);

Route::get('/laporan-posisi-keuangan/export-pdf', [\App\Http\Controllers\LaporanPosisiKeuanganController::class, 'exportPdf'])
    ->name('laporan-posisi-keuangan.export-pdf')
    ->middleware(['auth']);

Route::get('/laporan-laba-rugi/export-pdf', [\App\Http\Controllers\LaporanLabaRugiController::class, 'exportPdf'])
    ->name('laporan-laba-rugi.export-pdf')
    ->middleware(['auth']);

Route::get('/laporan-perubahan-ekuitas/export-pdf', [\App\Http\Controllers\LaporanPerubahanEkuitasController::class, 'exportPdf'])
    ->name('laporan-perubahan-ekuitas.export-pdf')
    ->middleware(['auth']);

Route::get('/laporan-arus-kas/export-pdf', [\App\Http\Controllers\LaporanArusKasController::class, 'exportPdf'])
    ->name('laporan-arus-kas.export-pdf')
    ->middleware(['auth']);


