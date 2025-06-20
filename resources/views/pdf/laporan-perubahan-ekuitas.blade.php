<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Perubahan Ekuitas</title>
    <style>
        @page {
            margin: 1cm;
            size: A4 portrait;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        
        .header h2 {
            margin: 5px 0;
            font-size: 16px;
            font-weight: bold;
        }
        
        .company-info {
            font-size: 10px;
            margin-top: 10px;
            line-height: 1.3;
        }
        
        .period-info {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .content {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .content table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #333;
        }
        
        .content th,
        .content td {
            border: 1px solid #333;
            padding: 8px;
            vertical-align: top;
        }
        
        .section-header {
            background-color: #2563eb;
            color: white;
            text-align: center;
            padding: 10px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .subsection-header {
            background-color: #dbeafe;
            color: #1e40af;
            padding: 8px;
            font-weight: bold;
            font-size: 12px;
            text-align: center;
        }
        
        .account-amount {
            text-align: right;
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
        
        .subtotal {
            background-color: #f3f4f6;
            font-weight: bold;
            padding: 8px;
        }
        
        .total {
            background-color: #2563eb;
            color: white;
            padding: 10px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .positive {
            color: #059669;
        }
        
        .negative {
            color: #dc2626;
        }
        
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
        }
    </style>
</head>
<body>
    @php
        $logo_path = public_path('images/logo-koperasi.png');
        $logo_src = file_exists($logo_path) 
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logo_path))
            : '';
    @endphp

    <div class="header">
        @if($logo_src)
            <div class="header-logo">
                <img src="{{ $logo_src }}" alt="Logo Koperasi" style="height: 60px;">
            </div>
        @endif
        <h1>Koperasi Simpan Pinjam dan Pembiayaan Syariah</h1>
        <h2>Citra Artha Mandiri</h2>
        <div class="company-info">
            RM Alang-alang, Sawah, Kubangpari, Kec. Kersana, Kabupaten Brebes, Jawa Tengah 52264<br>
            Telp: (0283) 4582620
        </div>
    </div>

    <div class="period-info">
        LAPORAN PERUBAHAN EKUITAS<br>
        Periode: {{ $periode }}<br>
    </div>

    <div class="content">
        <table>
            <!-- Header Row -->
            <thead>
                <tr>
                    <th class="section-header" style="width: 40%;">KETERANGAN</th>
                    <th class="section-header" style="width: 20%;">SALDO AWAL</th>
                    <th class="section-header" style="width: 20%;">PERUBAHAN</th>
                    <th class="section-header" style="width: 20%;">SALDO AKHIR</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ekuitas as $item)
                    <tr>
                        <td>{{ $item->nama_akun }}</td>
                        <td class="account-amount">Rp{{ number_format($item->saldo_awal, 0, ',', '.') }}</td>
                        <td class="account-amount {{ $item->perubahan >= 0 ? 'positive' : 'negative' }}">
                            {{ $item->perubahan >= 0 ? '+' : '' }}Rp{{ number_format($item->perubahan, 0, ',', '.') }}
                        </td>
                        <td class="account-amount">Rp{{ number_format($item->saldo_akhir, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="color: #999; font-style: italic; text-align: center;">
                            Tidak ada data ekuitas
                        </td>
                    </tr>
                @endforelse
                
                <!-- Laba/Rugi Periode Berjalan -->
                <tr>
                    <td><strong>{{ $is_profit ? 'Laba' : 'Rugi' }} Periode Berjalan</strong></td>
                    <td class="account-amount">Rp0</td>
                    <td class="account-amount {{ $is_profit ? 'positive' : 'negative' }}">
                        <strong>{{ $is_profit ? '+' : '' }}Rp{{ number_format($laba_rugi, 0, ',', '.') }}</strong>
                    </td>
                    <td class="account-amount {{ $is_profit ? 'positive' : 'negative' }}">
                        <strong>Rp{{ number_format(abs($laba_rugi), 0, ',', '.') }}</strong>
                    </td>
                </tr>
                
                <!-- Total -->
                <tr>
                    <td class="total">
                        <strong>TOTAL EKUITAS</strong>
                    </td>
                    <td class="total account-amount">
                        <strong>Rp{{ number_format($total_ekuitas_awal, 0, ',', '.') }}</strong>
                    </td>
                    <td class="total account-amount">
                        <strong>{{ $total_perubahan + $laba_rugi >= 0 ? '+' : '' }}Rp{{ number_format($total_perubahan + $laba_rugi, 0, ',', '.') }}</strong>
                    </td>
                    <td class="total account-amount">
                        <strong>Rp{{ number_format($total_ekuitas_akhir + abs($laba_rugi), 0, ',', '.') }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="footer">
        Dicetak pada: {{ $tanggal_cetak }}
    </div>
</body>
</html>
