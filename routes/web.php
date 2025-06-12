<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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


