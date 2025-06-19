<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Posisi Keuangan - {{ $periode }}</title>
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
            margin-bottom: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            vertical-align: top;
            font-size: 10px;
        }

        th {
            background-color: #f5f5f5;
            text-align: center;
            font-weight: bold;
        }

        .section-header {
            background-color: #e5e7eb;
            font-weight: bold;
            text-align: center;
        }

        .account-name {
            text-align: left;
        }

        .account-amount {
            text-align: right;
            font-family: Arial, sans-serif;
        }

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

        .empty-cell {
            background-color: #fafafa;
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
    LAPORAN POSISI KEUANGAN<br>
    Periode: {{ $periode }}<br>
</div>

    <table>
        <thead>
            <tr>
                <th colspan="2">Aktiva</th>
                <th colspan="2">Pasiva</th>
            </tr>
        </thead>
        <tbody>
            {{-- Aktiva Lancar & Kewajiban --}}
            <tr>
                <td colspan="2" class="section-header">Aktiva Lancar</td>
                <td colspan="2" class="section-header">Kewajiban</td>
            </tr>
            @php 
                // Convert collections to arrays with numeric index
                $aktiva_lancar_array = is_object($aktiva_lancar) ? $aktiva_lancar->values()->toArray() : array_values($aktiva_lancar);
                $kewajiban_array = is_object($kewajiban) ? $kewajiban->values()->toArray() : array_values($kewajiban);
                $max1 = max(count($aktiva_lancar_array), count($kewajiban_array)); 
            @endphp
            @for($i = 0; $i < $max1; $i++)
            <tr>
                {{-- Aktiva Lancar --}}
                @if(isset($aktiva_lancar_array[$i]) && !empty($aktiva_lancar_array[$i]))
                    <td class="account-name">{{ $aktiva_lancar_array[$i]->nama_akun ?? $aktiva_lancar_array[$i]->account_name ?? $aktiva_lancar_array[$i]->name ?? 'Nama Akun Tidak Tersedia' }}</td>
                    <td class="account-amount">Rp{{ number_format($aktiva_lancar_array[$i]->saldo ?? $aktiva_lancar_array[$i]->balance ?? 0, 0, ',', '.') }}</td>
                @else
                    <td class="account-name empty-cell">&nbsp;</td>
                    <td class="account-amount empty-cell">&nbsp;</td>
                @endif

                {{-- Kewajiban --}}
                @if(isset($kewajiban_array[$i]) && !empty($kewajiban_array[$i]))
                    <td class="account-name">{{ $kewajiban_array[$i]->nama_akun ?? $kewajiban_array[$i]->account_name ?? $kewajiban_array[$i]->name ?? 'Nama Akun Tidak Tersedia' }}</td>
                    <td class="account-amount">Rp{{ number_format($kewajiban_array[$i]->saldo ?? $kewajiban_array[$i]->balance ?? 0, 0, ',', '.') }}</td>
                @else
                    <td class="account-name empty-cell">&nbsp;</td>
                    <td class="account-amount empty-cell">&nbsp;</td>
                @endif
            </tr>
            @endfor

            <tr class="total-row">
                <td class="account-name">TOTAL AKTIVA LANCAR</td>
                <td class="account-amount">Rp{{ number_format($total_aktiva_lancar, 0, ',', '.') }}</td>
                <td class="account-name">TOTAL KEWAJIBAN</td>
                <td class="account-amount">Rp{{ number_format($total_kewajiban, 0, ',', '.') }}</td>
            </tr>

            {{-- Aktiva Tetap & Ekuitas --}}
            <tr>
                <td colspan="2" class="section-header">Aktiva Tetap</td>
                <td colspan="2" class="section-header">Ekuitas</td>
            </tr>
            @php 
                // Convert collections to arrays with numeric index
                $ekuitas_array = is_object($ekuitas) ? $ekuitas->values()->toArray() : array_values($ekuitas);
                $aktiva_tetap_array = is_object($aktiva_tetap) ? $aktiva_tetap->values()->toArray() : array_values($aktiva_tetap);
                $max2 = max(count($aktiva_tetap_array), count($ekuitas_array)); 
            @endphp
            @for($i = 0; $i < $max2; $i++)
            <tr>
                {{-- Aktiva Tetap --}}
                @if(isset($aktiva_tetap_array[$i]) && !empty($aktiva_tetap_array[$i]))
                    <td class="account-name">{{ $aktiva_tetap_array[$i]->nama_akun ?? 'Nama Akun Tidak Tersedia' }}</td>
                    <td class="account-amount">Rp{{ number_format($aktiva_tetap_array[$i]->saldo ?? 0, 0, ',', '.') }}</td>
                @else
                    <td class="account-name empty-cell">&nbsp;</td>
                    <td class="account-amount empty-cell">&nbsp;</td>
                @endif

                {{-- Ekuitas - Fixed Version --}}
                @if(isset($ekuitas_array[$i]) && !empty($ekuitas_array[$i]))
                    <td class="account-name">
                        {{ $ekuitas_array[$i]->nama_akun ?? $ekuitas_array[$i]->account_name ?? $ekuitas_array[$i]->name ?? 'Nama Akun Tidak Tersedia' }}
                    </td>
                    <td class="account-amount">
                        Rp{{ number_format($ekuitas_array[$i]->saldo ?? $ekuitas_array[$i]->balance ?? 0, 0, ',', '.') }}
                    </td>
                @else
                    <td class="account-name empty-cell">
                        @if($i == 0)
                            DEBUG: Ekuitas array count = {{ count($ekuitas_array) }}, isset = {{ isset($ekuitas_array[0]) ? 'TRUE' : 'FALSE' }}
                        @else
                            &nbsp;
                        @endif
                    </td>
                    <td class="account-amount empty-cell">&nbsp;</td>
                @endif
            </tr>
            @endfor

            <tr class="total-row">
                <td class="account-name">TOTAL AKTIVA TETAP</td>
                <td class="account-amount">Rp{{ number_format($total_aktiva_tetap, 0, ',', '.') }}</td>
                <td class="account-name">TOTAL EKUITAS</td>
                <td class="account-amount">Rp{{ number_format($total_ekuitas, 0, ',', '.') }}</td>
            </tr>

            {{-- Grand Total --}}
            <tr class="total-row">
                <td class="account-name"><strong>TOTAL AKTIVA</strong></td>
                <td class="account-amount"><strong>Rp{{ number_format($total_aktiva, 0, ',', '.') }}</strong></td>
                <td class="account-name"><strong>TOTAL PASIVA</strong></td>
                <td class="account-amount"><strong>Rp{{ number_format($total_pasiva, 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="balance-info {{ $is_balanced ? 'balanced' : 'unbalanced' }}">
        @if($is_balanced)
            <strong>POSISI KEUANGAN SEIMBANG</strong><br>
            Total Aktiva = Total Pasiva = Rp{{ number_format($total_aktiva, 0, ',', '.') }}
        @else
            <strong>POSISI KEUANGAN TIDAK SEIMBANG</strong><br>
            Selisih: Rp{{ number_format($selisih, 0, ',', '.') }}
        @endif
    </div>

    <div class="footer">
        Laporan ini digenerate otomatis pada {{ $tanggal_cetak }}
    </div>

</body>
</html>