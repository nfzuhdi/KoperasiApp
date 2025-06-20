<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Arus Kas</title>
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
        
        .kas-awal {
            background-color: #e5e7eb;
            font-weight: bold;
            padding: 8px;
        }
        
        .kas-bersih {
            background-color: #fff3cd;
            font-weight: bold;
            padding: 8px;
        }
        
        .positive {
            color: #059669;
        }
        
        .negative {
            color: #dc2626;
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
        LAPORAN ARUS KAS<br>
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
                <!-- Kas Awal -->
                <tr>
                    <td class="kas-awal account-name">
                        <strong>Kas dan Setara Kas Awal Periode</strong>
                    </td>
                    <td class="kas-awal account-amount">
                        <strong>Rp{{ number_format($kas_awal, 0, ',', '.') }}</strong>
                    </td>
                </tr>
                
                <!-- Arus Kas dari Aktivitas Operasi -->
                <tr>
                    <td class="subsection-header" colspan="2">ARUS KAS DARI AKTIVITAS OPERASI</td>
                </tr>
                
                @forelse($arus_operasi as $item)
                    <tr>
                        <td class="account-name">{{ $item->keterangan }}</td>
                        <td class="account-amount {{ $item->type == 'masuk' ? 'positive' : 'negative' }}">
                            {{ $item->type == 'masuk' ? '+' : '-' }}Rp{{ number_format($item->jumlah, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="account-name" style="color: #999; font-style: italic;">Tidak ada aktivitas operasi</td>
                        <td class="account-amount" style="color: #999;">Rp0</td>
                    </tr>
                @endforelse

                <!-- Total Operasi -->
                <tr class="total-row">
                    <td class="subtotal account-name">
                        <strong>Arus Kas Bersih dari Aktivitas Operasi</strong>
                    </td>
                    <td class="subtotal account-amount {{ $total_operasi >= 0 ? 'positive' : 'negative' }}">
                        <strong>{{ $total_operasi >= 0 ? '+' : '' }}Rp{{ number_format($total_operasi, 0, ',', '.') }}</strong>
                    </td>
                </tr>
                
                <!-- Arus Kas dari Aktivitas Investasi -->
                <tr>
                    <td class="subsection-header" colspan="2">ARUS KAS DARI AKTIVITAS INVESTASI</td>
                </tr>
                
                @forelse($arus_investasi as $item)
                    <tr>
                        <td class="account-name">{{ $item->keterangan }}</td>
                        <td class="account-amount {{ $item->type == 'masuk' ? 'positive' : 'negative' }}">
                            {{ $item->type == 'masuk' ? '+' : '-' }}Rp{{ number_format($item->jumlah, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="account-name" style="color: #999; font-style: italic;">Tidak ada aktivitas investasi</td>
                        <td class="account-amount" style="color: #999;">Rp0</td>
                    </tr>
                @endforelse

                <!-- Total Investasi -->
                <tr class="total-row">
                    <td class="subtotal account-name">
                        <strong>Arus Kas Bersih dari Aktivitas Investasi</strong>
                    </td>
                    <td class="subtotal account-amount {{ $total_investasi >= 0 ? 'positive' : 'negative' }}">
                        <strong>{{ $total_investasi >= 0 ? '+' : '' }}Rp{{ number_format($total_investasi, 0, ',', '.') }}</strong>
                    </td>
                </tr>
                
                <!-- Arus Kas dari Aktivitas Pendanaan -->
                <tr>
                    <td class="subsection-header" colspan="2">ARUS KAS DARI AKTIVITAS PENDANAAN</td>
                </tr>
                
                @forelse($arus_pendanaan as $item)
                    <tr>
                        <td class="account-name">{{ $item->keterangan }}</td>
                        <td class="account-amount {{ $item->type == 'masuk' ? 'positive' : 'negative' }}">
                            {{ $item->type == 'masuk' ? '+' : '-' }}Rp{{ number_format($item->jumlah, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="account-name" style="color: #999; font-style: italic;">Tidak ada aktivitas pendanaan</td>
                        <td class="account-amount" style="color: #999;">Rp0</td>
                    </tr>
                @endforelse

                <!-- Total Pendanaan -->
                <tr class="total-row">
                    <td class="subtotal account-name">
                        <strong>Arus Kas Bersih dari Aktivitas Pendanaan</strong>
                    </td>
                    <td class="subtotal account-amount {{ $total_pendanaan >= 0 ? 'positive' : 'negative' }}">
                        <strong>{{ $total_pendanaan >= 0 ? '+' : '' }}Rp{{ number_format($total_pendanaan, 0, ',', '.') }}</strong>
                    </td>
                </tr>
                
                <!-- Arus Kas Bersih -->
                <tr>
                    <td class="kas-bersih account-name">
                        <strong>Kenaikan (Penurunan) Bersih Kas dan Setara Kas</strong>
                    </td>
                    <td class="kas-bersih account-amount {{ $arus_kas_bersih >= 0 ? 'positive' : 'negative' }}">
                        <strong>{{ $arus_kas_bersih >= 0 ? '+' : '' }}Rp{{ number_format($arus_kas_bersih, 0, ',', '.') }}</strong>
                    </td>
                </tr>

                <!-- Kas Akhir -->
                <tr>
                    <td class="total account-name">
                        <strong>Kas dan Setara Kas Akhir Periode</strong>
                    </td>
                    <td class="total account-amount">
                        <strong>Rp{{ number_format($kas_akhir, 0, ',', '.') }}</strong>
                    </td>
                </tr>
            </tbody>
    </table>

    <div class="footer">
        Laporan ini digenerate otomatis pada {{ $tanggal_cetak }}
    </div>
</body>
</html>
