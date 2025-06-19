<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kwitansi Pembayaran</title>
    <style>
        @page {
            size: 14.8cm 21cm; /* A5 size */
            margin: 1cm;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px; /* reduced from 12px */
            background-color: #f9f9f9;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .invoice-container {
            max-width: 100%;
            margin: 0 auto;
            padding: 15px; /* reduced from 30px */
            border: 1px solid #ddd;
            background-color: white;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 5px; /* reduced from 10px */
            margin-bottom: 10px; /* reduced from 20px */
        }

        .header img {
            max-height: 40px; /* reduced from 60px */
            margin-bottom: 5px;
        }

        .header h2 {
            margin: 0;
            font-size: 16px; /* reduced from 20px */
        }

        .header p {
            margin: 2px 0;
            font-size: 11px; /* reduced from 13px */
            color: #666;
        }

        .info-table {
            width: 100%;
            margin-bottom: 10px; /* reduced from 20px */
            border-collapse: collapse;
        }

        .info-table td {
            padding: 4px 2px; /* reduced from 8px 4px */
            vertical-align: top;
        }

        .amount {
            font-size: 14px; /* reduced from 18px */
            font-weight: bold;
            color: #2d6a4f;
        }

        .footer {
            text-align: center;
            margin-top: 15px; /* reduced from 30px */
            font-size: 9px; /* reduced from 12px */
            color: #888;
        }

        h3 {
            font-size: 14px; /* add this for the title */
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <img src="{{ public_path('css/filament/filament/logo.svg') }}" alt="Logo Koperasi">
            <h2>KOPERASI SIMPAN PINJAM DAN PEMBIAYAAN SYARIAH</h2>
            <p>Jl. Contoh No. 123, Kota, Provinsi, Kode Pos</p>
            <p>Telp: (021) 1234567 | Email: info@koperasisyariah.com</p>
        </div>

        <h3 style="text-align:center; margin-bottom: 10px;">BUKTI PEMBAYARAN</h3>
        <p style="text-align:center; margin-bottom: 30px;">ID Transaksi: {{ $payment->id }}</p>

        <table class="info-table">
            <tr>
                <td>Tanggal Pembayaran</td>
                <td>{{ $payment->created_at->format('d M Y, H:i') }}</td>
            </tr>
            <tr>
                <td>Nomor Rekening</td>
                <td>{{ $saving->account_number }}</td>
            </tr>
            <tr>
                <td>Nama Anggota</td>
                <td>{{ $member->full_name }}</td>
            </tr>
            <tr>
                <td>Metode Pembayaran</td>
                <td>{{ ucfirst($payment->payment_method) }}</td>
            </tr>
            <tr>
                <td>Nomor Referensi</td>
                <td>{{ $payment->reference_number ?? '-' }}</td>
            </tr>
            <tr>
                <td>Jumlah Pembayaran</td>
                <td class="amount">Rp {{ number_format($payment->amount, 2, ',', '.') }}</td>
            </tr>
        </table>

        <div class="footer">
            <p>Dokumen ini diterbitkan secara elektronik dan sah tanpa tanda tangan.</p>
            <p>Terima kasih atas kepercayaan Anda kepada Koperasi Syariah kami.</p>
        </div>
    </div>
</body>
</html>
