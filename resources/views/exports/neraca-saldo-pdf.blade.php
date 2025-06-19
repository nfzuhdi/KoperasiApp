<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Neraca Saldo - {{ $periode }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }

        .header-logo {
            text-align: center;
            margin-bottom: 15px;
        }

        .header-logo img {
            height: 60px;
            width: auto;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header h2 {
            margin: 5px 0;
            font-size: 14px;
            font-weight: normal;
        }

        .company-info {
            font-size: 9px;
            margin-top: 10px;
            color: #666;
        }

        .period-info {
            text-align: center;
            margin-bottom: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            font-size: 10px;
        }

        th {
            background-color: #f5f5f5;
            text-align: center;
            font-weight: bold;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }

        .total-row {
            background-color: #fff3cd;
            font-weight: bold;
        }

        .total-row td {
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
        }

        .balance-info {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            font-size: 10px;
            border: 1px solid #dee2e6;
        }

        .balanced {
            background-color: #d4edda;
            color: #155724;
        }

        .unbalanced {
            background-color: #f8d7da;
            color: #721c24;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }

        @media print {
            body { margin: 0; padding: 15px; }
            th, td { font-size: 9px; }
        }
    </style>
</head>
<body>

@php
    $logo_path = public_path('css/filament/filament/logo.svg');
    $logo_data = base64_encode(file_get_contents($logo_path));
    $logo_src = 'data:image/svg+xml;base64,' . $logo_data;
@endphp

<div class="header">
    <div class="header-logo">
        <img src="{{ $logo_src }}" alt="Logo Koperasi">
    </div>
    <h1>Koperasi Simpan Pinjam dan Pembiayaan Syariah</h1>
    <h2>Citra Artha Mandiri</h2>
    <div class="company-info">
        RM Alang-alang, Sawah, Kubangpari, Kec. Kersana, Kabupaten Brebes, Jawa Tengah 52264<br>
        Telp: (0283) 4582620
    </div>
</div>

<div class="period-info">
    NERACA SALDO<br>
    Periode: {{ $periode }}<br>
</div>

<table>
    <thead>
        <tr>
            <th>Kode Akun</th>
            <th>Nama Akun</th>
            <th>Posisi Normal</th>
            <th>Debet</th>
            <th>Kredit</th>
        </tr>
    </thead>
    <tbody>
        @foreach($neraca_saldo as $item)
        <tr>
            <td class="text-center">{{ $item->kode_akun }}</td>
            <td class="text-left">{{ $item->nama_akun }}</td>
            <td class="text-center">{{ $item->posisi_normal }}</td>
            <td class="text-right">{{ $item->saldo_debet > 0 ? number_format($item->saldo_debet, 0, ',', '.') : '-' }}</td>
            <td class="text-right">{{ $item->saldo_kredit > 0 ? number_format($item->saldo_kredit, 0, ',', '.') : '-' }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="3" class="text-center">TOTAL</td>
            <td class="text-right">{{ number_format($total_debet, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($total_kredit, 0, ',', '.') }}</td>
        </tr>
    </tbody>
</table>

<div class="balance-info {{ abs($total_debet - $total_kredit) < 0.01 ? 'balanced' : 'unbalanced' }}">
    @if(abs($total_debet - $total_kredit) < 0.01)
        <strong>NERACA SEIMBANG</strong><br>
        Total Debet = Total Kredit = {{ number_format($total_debet, 0, ',', '.') }}
    @else
        <strong>NERACA TIDAK SEIMBANG</strong><br>
        Selisih: {{ number_format($selisih, 0, ',', '.') }}<br>
        (Debet: {{ number_format($total_debet, 0, ',', '.') }}, Kredit: {{ number_format($total_kredit, 0, ',', '.') }})
    @endif
</div>

<div class="footer">
    Laporan ini digenerate otomatis pada {{ $tanggal_cetak }}
</div>


</body>
</html>
