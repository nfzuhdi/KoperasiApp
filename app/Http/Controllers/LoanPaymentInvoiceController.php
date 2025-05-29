<?php

namespace App\Http\Controllers;

use App\Models\LoanPayment;
use Illuminate\Http\Request;

class LoanPaymentInvoiceController extends Controller
{
    public function generateInvoice(Request $request, $record)
    {
        $payment = LoanPayment::with(['loan.member', 'loan.loanProduct'])->findOrFail($record);
        
        return view('invoices.loan-payment', [
            'payment' => $payment,
            'invoiceNumber' => 'INV-' . $payment->id . '-' . now()->format('Ymd'),
            'date' => now()->format('d/m/Y'),
        ]);
    }
}
