<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Laba Rugi</title>
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
            margin-top: 20px;
            border: 1px solid #333;
        }

        th, td {
            border: 1px solid #333;
            padding: 8px;
            vertical-align: top;
        }

        .account-name {
            text-align: left;
        }

        .account-amount {
            text-align: right;
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
        
        .section-header {
            background-color: #f5f5f5;
            color: #333;
            text-align: center;
            padding: 10px;
            font-weight: bold;
            font-size: 12px;
        }

        .subsection-header {
            background-color: #e5e7eb;
            color: #333;
            padding: 8px;
            font-weight: bold;
            font-size: 11px;
            text-align: center;
        }

        .subtotal {
            background-color: #f3f4f6;
            font-weight: bold;
            padding: 8px;
        }

        .total {
            background-color: #f5f5f5;
            color: #333;
            padding: 10px;
            font-weight: bold;
            font-size: 12px;
        }

        .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
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
        LAPORAN LABA RUGI<br>
        Periode: {{ $periode }}<br>
    </div>

    <table>
        <!-- Header Row -->
        <thead>
            <tr>
                <th class="section-header" style="width: 70%;">KETERANGAN</th>
                <th class="section-header" style="width: 30%;">JUMLAH</th>
            </tr>
        </thead>
        <tbody>
                <!-- Pendapatan Section -->
                <tr>
                    <td class="subsection-header" colspan="2">PENDAPATAN</td>
                </tr>
                
                @forelse($pendapatan as $item)
                    <tr>
                        <td class="account-name">{{ $item->nama_akun }}</td>
                        <td class="account-amount">Rp{{ number_format($item->saldo, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="account-name" style="color: #999; font-style: italic;">Tidak ada pendapatan</td>
                        <td class="account-amount" style="color: #999;">Rp0</td>
                    </tr>
                @endforelse
                
                <!-- Total Pendapatan -->
                <tr class="total-row">
                    <td class="subtotal account-name">
                        <strong>TOTAL PENDAPATAN</strong>
                    </td>
                    <td class="subtotal account-amount">
                        <strong>Rp{{ number_format($total_pendapatan, 0, ',', '.') }}</strong>
                    </td>
                </tr>

                <!-- Beban Section -->
                <tr>
                    <td class="subsection-header" colspan="2">BEBAN</td>
                </tr>

                @forelse($beban as $item)
                    <tr>
                        <td class="account-name">{{ $item->nama_akun }}</td>
                        <td class="account-amount">Rp{{ number_format($item->saldo, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="account-name" style="color: #999; font-style: italic;">Tidak ada beban</td>
                        <td class="account-amount" style="color: #999;">Rp0</td>
                    </tr>
                @endforelse

                <!-- Total Beban -->
                <tr class="total-row">
                    <td class="subtotal account-name">
                        <strong>TOTAL BEBAN</strong>
                    </td>
                    <td class="subtotal account-amount">
                        <strong>Rp{{ number_format($total_beban, 0, ',', '.') }}</strong>
                    </td>
                </tr>
                
                <!-- Laba/Rugi -->
                <tr>
                    <td class="total account-name">
                        <strong>{{ $is_profit ? 'LABA BERSIH' : 'RUGI BERSIH' }}</strong>
                    </td>
                    <td class="total account-amount">
                        <strong>Rp{{ number_format(abs($laba_rugi), 0, ',', '.') }}</strong>
                    </td>
                </tr>
            </tbody>
    </table>

    <div class="footer">
        Laporan ini digenerate otomatis pada {{ $tanggal_cetak }}
    </div>
</body>
</html>
