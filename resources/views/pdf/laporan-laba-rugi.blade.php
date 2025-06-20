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
        }
        
        .account-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        
        .account-name {
            flex: 1;
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
        
        .total-row {
            display: flex;
            justify-content: space-between;
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
        LAPORAN LABA RUGI<br>
        Periode: {{ $periode }}<br>
    </div>

    <div class="content">
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
                        <td>{{ $item->nama_akun }}</td>
                        <td class="account-amount">Rp{{ number_format($item->saldo, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td style="color: #999; font-style: italic;">Tidak ada pendapatan</td>
                        <td class="account-amount" style="color: #999;">Rp0</td>
                    </tr>
                @endforelse
                
                <!-- Total Pendapatan -->
                <tr>
                    <td class="subtotal">
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
                        <td>{{ $item->nama_akun }}</td>
                        <td class="account-amount">Rp{{ number_format($item->saldo, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td style="color: #999; font-style: italic;">Tidak ada beban</td>
                        <td class="account-amount" style="color: #999;">Rp0</td>
                    </tr>
                @endforelse
                
                <!-- Total Beban -->
                <tr>
                    <td class="subtotal">
                        <strong>TOTAL BEBAN</strong>
                    </td>
                    <td class="subtotal account-amount">
                        <strong>Rp{{ number_format($total_beban, 0, ',', '.') }}</strong>
                    </td>
                </tr>
                
                <!-- Laba/Rugi -->
                <tr>
                    <td class="total">
                        <strong>{{ $is_profit ? 'LABA BERSIH' : 'RUGI BERSIH' }}</strong>
                    </td>
                    <td class="total account-amount">
                        <strong>Rp{{ number_format(abs($laba_rugi), 0, ',', '.') }}</strong>
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
