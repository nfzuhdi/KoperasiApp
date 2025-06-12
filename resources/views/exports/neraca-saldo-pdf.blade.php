<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Neraca Saldo - {{ $periode }}</title>
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
            margin: 0 0 10px 0;
            text-transform: uppercase;
        }
        
        .header .periode {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .header .tanggal {
            font-size: 12px;
            margin: 5px 0;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .table th {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }
        
        .table td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            font-size: 11px;
        }
        
        .table .text-center {
            text-align: center;
        }
        
        .table .text-right {
            text-align: right;
        }
        
        .table .text-left {
            text-align: left;
        }
        
        .table .total-row {
            background-color: #fff3cd;
            font-weight: bold;
        }
        
        .table .total-row td {
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
        }
        
        .summary {
            margin-top: 20px;
            font-size: 11px;
        }
        
        .summary .balance-info {
            text-align: center;
            margin-top: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        
        .balance-info.balanced {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .balance-info.unbalanced {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            color: #666;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 15px;
            }
            
            .header h1 {
                font-size: 16px;
            }
            
            .table th,
            .table td {
                font-size: 10px;
                padding: 4px 6px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Neraca Saldo</h1>
        <div class="periode">Periode: {{ $periode }}</div>
        <div class="tanggal">Dicetak: {{ $tanggal_cetak }}</div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 15%;">Kode Akun</th>
                <th style="width: 40%;">Nama Akun</th>
                <th style="width: 15%;">Posisi Normal</th>
                <th style="width: 15%;">Debet</th>
                <th style="width: 15%;">Kredit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($neraca_saldo as $item)
            <tr>
                <td class="text-center">{{ $item->kode_akun }}</td>
                <td class="text-left">{{ $item->nama_akun }}</td>
                <td class="text-center">{{ $item->posisi_normal }}</td>
                <td class="text-right">
                    @if($item->saldo_debet > 0)
                        {{ number_format($item->saldo_debet, 0, ',', '.') }}
                    @endif
                </td>
                <td class="text-right">
                    @if($item->saldo_kredit > 0)
                        {{ number_format($item->saldo_kredit, 0, ',', '.') }}
                    @endif
                </td>
            </tr>
            @endforeach
            
            <tr class="total-row">
                <td class="text-center" colspan="3"><strong>TOTAL</strong></td>
                <td class="text-right"><strong>{{ number_format($total_debet, 0, ',', '.') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($total_kredit, 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="summary">
        <div class="balance-info {{ $is_balanced ? 'balanced' : 'unbalanced' }}">
            @if($is_balanced)
                <strong>✓ NERACA SEIMBANG</strong><br>
                Total Debet = Total Kredit = {{ number_format($total_debet, 0, ',', '.') }}
            @else
                <strong>⚠ NERACA TIDAK SEIMBANG</strong><br>
                Selisih: {{ number_format($selisih, 0, ',', '.') }}<br>
                (Total Debet: {{ number_format($total_debet, 0, ',', '.') }} | Total Kredit: {{ number_format($total_kredit, 0, ',', '.') }})
            @endif
        </div>
    </div>

    <div class="footer">
        <p>Laporan ini digenerate secara otomatis pada {{ now()->locale('id')->isoFormat('dddd, D MMMM Y [pukul] HH:mm') }}</p>
    </div>
</body>
</html>
