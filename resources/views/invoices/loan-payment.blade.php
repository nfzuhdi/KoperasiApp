<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice Pembayaran</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        font-size: 14px;
        line-height: 1.5;
        color: #333;
    }

    .invoice-box {
        max-width: 800px;
        margin: auto;
        padding: 30px;
        border: 1px solid #eee;
        box-shadow: 0 0 10px rgba(0, 0, 0, .15);
    }

    .invoice-header {
        text-align: center;
        margin-bottom: 20px;
    }

    .invoice-header h1 {
        color: #0066cc;
        font-size: 24px;
        margin-bottom: 0;
    }

    .invoice-details {
        margin-bottom: 30px;
    }

    .invoice-details table {
        width: 100%;
    }

    .invoice-details td {
        padding: 5px 0;
    }

    .invoice-details .label {
        font-weight: bold;
        width: 150px;
    }

    .payment-details {
        margin-bottom: 30px;
    }

    .payment-details table {
        width: 100%;
        border-collapse: collapse;
    }

    .payment-details th {
        background-color: #f2f2f2;
        text-align: left;
        padding: 10px;
        border: 1px solid #ddd;
    }

    .payment-details td {
        padding: 10px;
        border: 1px solid #ddd;
    }

    .total {
        text-align: right;
        margin-top: 20px;
    }

    .total .amount {
        font-weight: bold;
        font-size: 18px;
        color: #0066cc;
    }

    .footer {
        margin-top: 50px;
        text-align: center;
        color: #777;
        font-size: 12px;
    }

    .print-button {
        text-align: center;
        margin: 20px 0;
    }

    .print-button button {
        background-color: #0066cc;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
    }

    @media print {
        .print-button {
            display: none;
        }

        body {
            margin: 0;
            padding: 0;
        }

        .invoice-box {
            box-shadow: none;
            border: none;
        }
    }
    </style>
</head>

<body>
    <div class="print-button">
        <button onclick="window.print()">Cetak Invoice</button>
    </div>

    <div class="invoice-box">
        <div class="invoice-header">
            <div class="logo">
                <img src="{{ asset('css/filament/filament/logo.svg') }}" alt="Logo" style="max-height: 80px; margin-bottom: 10px;">
            </div>
            <h1>INVOICE PEMBAYARAN</h1>
            <p>{{ $invoiceNumber }}</p>
        </div>

        <div class="invoice-details">
            <table>
                <tr>
                    <td class="label">Tanggal:</td>
                    <td>{{ $date }}</td>
                </tr>
                <tr>
                    <td class="label">No. Referensi:</td>
                    <td>{{ $payment->reference_number }}</td>
                </tr>
                <tr>
                    <td class="label">No. Rekening:</td>
                    <td>{{ $payment->loan->account_number }}</td>
                </tr>
                <tr>
                    <td class="label">Nama Anggota:</td>
                    <td>{{ $payment->loan->member->full_name }}</td>
                </tr>
                <tr>
                    <td class="label">Produk Pembiayaan:</td>
                    <td>{{ $payment->loan->loanProduct->name }}</td>
                </tr>
                <tr>
                    <td class="label">Jenis Kontrak:</td>
                    <td>{{ $payment->loan->loanProduct->contract_type }}</td>
                </tr>
                <tr>
                    <td class="label">Status Pembayaran:</td>
                    <td>{{ ucfirst($payment->status) }}</td>
                </tr>
            </table>
        </div>

        <div class="payment-details">
            <h3>Detail Pembayaran</h3>
            <table>
                <thead>
                    <tr>
                        <th>Deskripsi</th>
                        <th>Periode</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            @if($payment->is_principal_return)
                            Pengembalian Pokok
                            @elseif($payment->loan->loanProduct->contract_type === 'Mudharabah' ||
                            $payment->loan->loanProduct->contract_type === 'Musyarakah')
                            Pembayaran Bagi Hasil
                            @elseif($payment->loan->loanProduct->contract_type === 'Murabahah')
                            Pembayaran Angsuran
                            @else
                            Pembayaran Pinjaman
                            @endif
                        </td>
                        <td>
                            @if($payment->is_principal_return)
                            Pengembalian Pokok
                            @else
                            Periode {{ $payment->payment_period }}
                            @endif
                        </td>
                        <td>Rp {{ number_format($payment->amount, 2) }}</td>
                    </tr>
                    @if($payment->fine > 0)
                    <tr>
                        <td>Denda Keterlambatan</td>
                        <td>-</td>
                        <td>Rp {{ number_format($payment->fine, 2) }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>

            <div class="total">
                <p>Total Pembayaran: <span class="amount">Rp
                        {{ number_format($payment->amount + $payment->fine, 2) }}</span></p>
            </div>
        </div>

        <div class="footer">
            <p>Terima kasih atas pembayaran Anda.</p>
            <p>Dokumen ini dihasilkan secara otomatis dan sah tanpa tanda tangan.</p>
        </div>
    </div>

    <script>
    // Auto print when page loads (optional)
    // window.onload = function() {
    //     window.print();
    // }
    </script>
</body>

</html>
