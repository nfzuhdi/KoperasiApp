<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Posisi Keuangan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        
        .header h2 {
            font-size: 16px;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .header p {
            font-size: 14px;
            margin: 5px 0;
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
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            background-color: #f3f4f6;
            font-weight: bold;
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
        
        .balance-info {
            margin-top: 30px;
            text-align: center;
            padding: 15px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
        }
        
        .balance-status {
            font-weight: bold;
            font-size: 14px;
        }
        
        .balanced {
            color: #059669;
        }
        
        .unbalanced {
            color: #dc2626;
        }
        
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN POSISI KEUANGAN</h1>
        <h2>PT XXX</h2>
        <p>Periode {{ $periode }}</p>
    </div>

    <div class="content">
        <table>
            <!-- Header Row -->
            <thead>
                <tr>
                    <th class="section-header" style="width: 50%;">AKTIVA</th>
                    <th class="section-header" style="width: 50%;">PASIVA</th>
                </tr>
            </thead>
            <tbody>
                <!-- Aktiva & Pasiva Category Headers -->
                <tr>
                    <td style="background-color: #e5e7eb; color: #374151; padding: 8px; font-weight: bold; text-align: center; border: 1px solid #333;">
                        Aktiva
                    </td>
                    <td style="background-color: #e5e7eb; color: #374151; padding: 8px; font-weight: bold; text-align: center; border: 1px solid #333;">
                        Pasiva
                    </td>
                </tr>

                <!-- Aktiva Lancar & Kewajiban Headers -->
                <tr>
                    <td class="subsection-header">Aktiva Lancar</td>
                    <td class="subsection-header">Kewajiban</td>
                </tr>

                <!-- Content Rows -->
                @php
                    $maxRows = max($aktiva_lancar->count(), $kewajiban->count());
                @endphp

                @for($i = 0; $i < $maxRows; $i++)
                    <tr>
                        <!-- Aktiva Lancar Item -->
                        <td>
                            @if($aktiva_lancar->has($i))
                                <div class="account-item">
                                    <span class="account-name">{{ $aktiva_lancar[$i]->nama_akun }}</span>
                                    <span class="account-amount">Rp{{ number_format($aktiva_lancar[$i]->saldo, 0, ',', '.') }}</span>
                                </div>
                            @else
                                <div class="account-item">
                                    <span class="account-name" style="color: #999; font-style: italic;">Aktiva Lancar Lainnya</span>
                                    <span class="account-amount" style="color: #999;">Rp0</span>
                                </div>
                            @endif
                        </td>

                        <!-- Kewajiban Item -->
                        <td>
                            @if($kewajiban->has($i))
                                <div class="account-item">
                                    <span class="account-name">{{ $kewajiban[$i]->nama_akun }}</span>
                                    <span class="account-amount">Rp{{ number_format($kewajiban[$i]->saldo, 0, ',', '.') }}</span>
                                </div>
                            @else
                                <div class="account-item">
                                    <span class="account-name" style="color: #999; font-style: italic;">Kewajiban Lancar Lainnya</span>
                                    <span class="account-amount" style="color: #999;">Rp0</span>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endfor

                <!-- Subtotal Row -->
                <tr>
                    <td class="subtotal">
                        <div class="total-row">
                            <span>TOTAL</span>
                            <span>Rp{{ number_format($total_aktiva_lancar, 0, ',', '.') }}</span>
                        </div>
                    </td>
                    <td class="subtotal">
                        <div class="total-row">
                            <span>TOTAL</span>
                            <span>Rp{{ number_format($total_kewajiban, 0, ',', '.') }}</span>
                        </div>
                    </td>
                </tr>

                <!-- Aktiva Tetap & Ekuitas Headers -->
                <tr>
                    <td class="subsection-header">Aktiva Tetap</td>
                    <td class="subsection-header">Ekuitas</td>
                </tr>

                <!-- Aktiva Tetap & Ekuitas Content -->
                @php
                    $maxRows2 = max($aktiva_tetap->count(), $ekuitas->count());
                @endphp

                @for($i = 0; $i < $maxRows2; $i++)
                    <tr>
                        <!-- Aktiva Tetap Item -->
                        <td>
                            @if($aktiva_tetap->has($i))
                                <div class="account-item">
                                    <span class="account-name">{{ $aktiva_tetap[$i]->nama_akun }}</span>
                                    <span class="account-amount">Rp{{ number_format($aktiva_tetap[$i]->saldo, 0, ',', '.') }}</span>
                                </div>
                            @else
                                <div class="account-item">
                                    <span class="account-name" style="color: #999; font-style: italic;">Inventaris</span>
                                    <span class="account-amount" style="color: #999;">Rp0</span>
                                </div>
                            @endif
                        </td>

                        <!-- Ekuitas Item -->
                        <td>
                            @if($ekuitas->has($i))
                                <div class="account-item">
                                    <span class="account-name">{{ $ekuitas[$i]->nama_akun }}</span>
                                    <span class="account-amount">Rp{{ number_format($ekuitas[$i]->saldo, 0, ',', '.') }}</span>
                                </div>
                            @else
                                <div class="account-item">
                                    <span class="account-name" style="color: #999; font-style: italic;">Prive</span>
                                    <span class="account-amount" style="color: #999;">Rp0</span>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endfor

                <!-- Subtotal Row 2 -->
                <tr>
                    <td class="subtotal">
                        <div class="total-row">
                            <span>TOTAL</span>
                            <span>Rp{{ number_format($total_aktiva_tetap, 0, ',', '.') }}</span>
                        </div>
                    </td>
                    <td class="subtotal">
                        <div class="total-row">
                            <span>TOTAL</span>
                            <span>Rp{{ number_format($total_ekuitas, 0, ',', '.') }}</span>
                        </div>
                    </td>
                </tr>

                <!-- Grand Total Row -->
                <tr>
                    <td class="total">
                        <div class="total-row">
                            <span>TOTAL AKTIVA</span>
                            <span>Rp{{ number_format($total_aktiva, 0, ',', '.') }}</span>
                        </div>
                    </td>
                    <td class="total">
                        <div class="total-row">
                            <span>TOTAL PASIVA</span>
                            <span>Rp{{ number_format($total_pasiva, 0, ',', '.') }}</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Balance Information -->
    <div class="balance-info">
        <div class="balance-status {{ $is_balanced ? 'balanced' : 'unbalanced' }}">
            @if($is_balanced)
                POSISI KEUANGAN SEIMBANG
            @else
                POSISI KEUANGAN TIDAK SEIMBANG
                <br>
                Selisih: Rp{{ number_format($selisih, 0, ',', '.') }}
            @endif
        </div>
    </div>

    <div class="footer">
        Dicetak pada: {{ $tanggal_cetak }}
    </div>
</body>
</html>
