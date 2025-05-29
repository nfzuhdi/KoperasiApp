<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/loan-payment/invoice/{record}', [\App\Http\Controllers\LoanPaymentInvoiceController::class, 'generateInvoice'])
    ->name('loan-payment.invoice')
    ->middleware(['auth']);


