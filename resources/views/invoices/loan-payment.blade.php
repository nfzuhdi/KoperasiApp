<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Bukti Pembayaran</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        line-height: 1.5;
        color: #333;
        margin: 0;
        padding: 0;
    }

    .invoice-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
        border: 1px solid #ddd;
    }

    .header {
        text-align: center;
        border-bottom: 2px solid #000;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }

    .header h1 {
        font-size: 18px;
        margin: 0;
        text-transform: uppercase;
    }

    .header p {
        margin: 5px 0;
    }

    .logo {
        max-height: 60px;
        margin-bottom: 10px;
    }

    .invoice-info {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .invoice-info-left,
    .invoice-info-right {
        width: 48%;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    table.details th {
        background-color: #f2f2f2;
        padding: 8px;
        text-align: left;
        border: 1px solid #ddd;
    }

    table.details td {
        padding: 8px;
        border: 1px solid #ddd;
    }

    .amount-table td {
        padding: 5px 0;
    }

    .amount-table .label {
        font-weight: bold;
        width: 150px;
    }

    .total {
        text-align: right;
        margin-top: 20px;
        font-weight: bold;
    }

    .footer {
        margin-top: 40px;
        text-align: center;
        font-size: 11px;
        color: #777;
    }

    .verification {
        margin-top: 30px;
        text-align: right;
        font-style: italic;
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
        font-size: 14px;
    }

    @media print {
        .print-button {
            display: none;
        }

        body {
            margin: 0;
            padding: 0;
        }

        .invoice-container {
            border: none;
        }
    }
    </style>
</head>

<body>
    <div class="print-button">
        <button onclick="window.print()">Cetak Bukti Pembayaran</button>
    </div>

    <div class="invoice-container">
        <div class="header">
            <img src="{{ asset('css/filament/filament/logo.svg') }}" alt="Logo Koperasi" class="logo">
            <h1>KOPERASI SIMPAN PINJAM DAN PEMBIAYAAN SYARIAH</h1>
            <p>Jl. Contoh No. 123, Kota, Provinsi, Kode Pos</p>
            <p>Telp: (021) 1234567 | Email: info@koperasisyariah.com</p>
        </div>

        <h2 style="text-align: center; margin: 20px 0;">BUKTI PEMBAYARAN</h2>
        <p style="text-align: center; margin-bottom: 20px;">{{ $invoiceNumber }}</p>

        <div class="invoice-info">
            <div class="invoice-info-left">
                <table>
                    <tr>
                        <td><strong>Nama Anggota</strong></td>
                        <td>: {{ $payment->loan->member->full_name }}</td>
                    </tr>
                    <tr>
                        <td><strong>No. Rekening</strong></td>
                        <td>: {{ $payment->loan->account_number }}</td>
                    </tr>
                    <tr>
                        <td><strong>Produk Pembiayaan</strong></td>
                        <td>: {{ $payment->loan->loanProduct->name }}</td>
                    </tr>
                    <tr>
                        <td><strong>Jenis Akad</strong></td>
                        <td>: {{ $payment->loan->loanProduct->contract_type }}</td>
                    </tr>
                </table>
            </div>
            <div class="invoice-info-right">
                <table>
                    <tr>
                        <td><strong>Tanggal</strong></td>
                        <td>: {{ $date }}</td>
                    </tr>
                    <tr>
                        <td><strong>No. Referensi</strong></td>
                        <td>: {{ $payment->reference_number }}</td>
                    </tr>
                    <tr>
                        <td><strong>Status</strong></td>
                        <td>: {{ ucfirst($payment->status) }}</td>
                    </tr>
                    <tr>
                        <td><strong>Metode Pembayaran</strong></td>
                        <td>: {{ ucfirst($payment->payment_method) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <table class="details">
            <thead>
                <tr>
                    <th>Deskripsi</th>
                    <th>Periode</th>
                    <th style="text-align: right;">Jumlah</th>
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
                    <td style="text-align: right;">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                </tr>
                @if($payment->fine > 0)
                <tr>
                    <td>Denda Keterlambatan</td>
                    <td>-</td>
                    <td style="text-align: right;">Rp {{ number_format($payment->fine, 0, ',', '.') }}</td>
                </tr>
                @endif
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" style="text-align: right;">Total</th>
                    <th style="text-align: right;">Rp
                        {{ number_format($payment->amount + $payment->fine, 0, ',', '.') }}</th>
                </tr>
            </tfoot>
        </table>

        <div class="verification">
            <p>Diverifikasi oleh: {{ $payment->reviewedBy->name ?? 'Admin' }}</p>
            <p>Tanggal verifikasi: {{ $payment->updated_at->format('d/m/Y H:i') }}</p>
        </div>

        <div class="footer">
            <p>Dokumen ini diterbitkan secara elektronik dan sah tanpa tanda tangan.</p>
            <p>Terima kasih atas kepercayaan Anda kepada Koperasi Syariah kami.</p>
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