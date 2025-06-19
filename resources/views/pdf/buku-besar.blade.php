<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Besar - {{ $bulan_nama }} {{ $tahun }}</title>
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
        
        .header .company-info {
            font-size: 9px;
            margin-top: 10px;
            color: #666;
        }
        
        .header-logo {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .header-logo img {
            height: 60px;
            width: auto;
        }
        
        .period-info {
            text-align: center;
            margin-bottom: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .account-section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .account-header {
            background-color: #f5f5f5;
            padding: 8px;
            border: 1px solid #ddd;
            margin-bottom: 0;
        }
        
        .account-header h3 {
            margin: 0;
            font-size: 11px;
            font-weight: bold;
        }
        
        .account-header .account-info {
            font-size: 9px;
            color: #666;
            margin-top: 3px;
        }
        
        .account-header .balance-info {
            float: right;
            text-align: right;
            margin-top: -20px;
        }
        
        .account-header .final-balance {
            font-size: 10px;
            font-weight: bold;
        }
        
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        
        .transactions-table th {
            background-color: #e9e9e9;
            border: 1px solid #ddd;
            padding: 6px 4px;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
        }
        
        .transactions-table td {
            border: 1px solid #ddd;
            padding: 4px;
            font-size: 9px;
            vertical-align: top;
        }
        
        .transactions-table .date-col {
            width: 12%;
            text-align: center;
        }
        
        .transactions-table .desc-col {
            width: 40%;
        }
        
        .transactions-table .amount-col {
            width: 16%;
            text-align: right;
        }
        
        .transactions-table .balance-col {
            width: 16%;
            text-align: right;
            font-weight: bold;
        }
        
        .transactions-table .opening-row {
            background-color: #f9f9f9;
            font-style: italic;
        }
        
        .transactions-table .total-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .positive-balance {
            color: #006600;
        }
        
        .negative-balance {
            color: #cc0000;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        



        @media print {
            body {
                margin: 0;
                padding: 15px;
            }

            .account-section {
                page-break-inside: avoid;
            }


        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-logo">
            <img src="{{ public_path('css/filament/filament/logo.svg') }}" alt="Logo Koperasi">
        </div>
        <h1>Koperasi Simpan Pinjam dan Pembiayaan Syariah</h1>
        <h2>Citra Artha Mandiri</h2>
        <div class="company-info">
            RM Alang-alang, Sawah, Kubangpari, Kec. Kersana, Kabupaten Brebes, Jawa Tengah 52264<br>
            Telp: (0283) 4582620
        </div>
    </div>

    <div class="period-info">
        BUKU BESAR<br>
        Periode: {{ $bulan_nama }} {{ $tahun }}
    </div>

    @if($entries->count() > 0)
        @foreach($entries as $akun_id => $jurnal_entries)
            @php
                $first_entry = $jurnal_entries->first();
                $akun = $first_entry?->akun;
                
                if (!$akun) {
                    continue;
                }

                // Calculate totals (exclude opening balance)
                $actual_transactions = $jurnal_entries->filter(function($entry) {
                    return $entry->keterangan !== 'Saldo Awal';
                });
                
                $total_debet = $actual_transactions->sum('debet');
                $total_kredit = $actual_transactions->sum('kredit');
                
                // Get final balance from last entry
                $last_entry = $jurnal_entries->last();
                $saldo_akhir = $last_entry ? $last_entry->saldo : 0;
            @endphp
            
            <div class="account-section">
                <div class="account-header">
                    <h3>{{ $akun->account_number }} - {{ $akun->account_name }}</h3>
                    <div class="account-info">
                        {{ $actual_transactions->count() }} transaksi dalam periode ini
                    </div>
                    <div class="balance-info">
                        <div style="font-size: 8px; color: #666;">Saldo Akhir:</div>
                        <div class="final-balance {{ $saldo_akhir >= 0 ? 'positive-balance' : 'negative-balance' }}">
                            Rp {{ number_format(abs($saldo_akhir), 0, ',', '.') }}
                            @php
                                // Determine correct position indicator
                                $isDebitAccount = $akun->account_position === 'debit';
                                $isPositiveBalance = $saldo_akhir >= 0;
                                
                                // For debit accounts: positive = D, negative = K
                                // For credit accounts: positive = K, negative = D
                                if ($isDebitAccount) {
                                    $showDebit = $isPositiveBalance;
                                } else {
                                    $showDebit = !$isPositiveBalance;
                                }
                            @endphp
                            {{ $showDebit ? '(D)' : '(K)' }}
                        </div>
                    </div>
                    <div style="clear: both;"></div>
                </div>

                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th class="date-col">Tanggal</th>
                            <th class="desc-col">Keterangan</th>
                            <th class="amount-col">Debit (Rp)</th>
                            <th class="amount-col">Kredit (Rp)</th>
                            <th class="balance-col">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($jurnal_entries as $entry)
                            <tr class="{{ $entry->keterangan == 'Saldo Awal' ? 'opening-row' : '' }}">
                                <td class="date-col">
                                    {{ $entry->keterangan == 'Saldo Awal' ? '-' : $entry->tanggal->format('d/m/Y') }}
                                </td>
                                <td class="desc-col">{{ $entry->keterangan }}</td>
                                <td class="amount-col">
                                    @if($entry->keterangan != 'Saldo Awal' && $entry->debet > 0)
                                        {{ number_format($entry->debet, 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="amount-col">
                                    @if($entry->keterangan != 'Saldo Awal' && $entry->kredit > 0)
                                        {{ number_format($entry->kredit, 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="balance-col {{ $entry->saldo >= 0 ? 'positive-balance' : 'negative-balance' }}">
                                    {{ number_format(abs($entry->saldo), 0, ',', '.') }}
                                    @php
                                        $isDebitAccount = $entry->akun->account_position === 'debit';
                                        $isPositiveBalance = $entry->saldo >= 0;
                                        
                                        // For debit accounts: positive = D, negative = K
                                        // For credit accounts: positive = K, negative = D
                                        if ($isDebitAccount) {
                                            $showDebit = $isPositiveBalance;
                                        } else {
                                            $showDebit = !$isPositiveBalance;
                                        }
                                    @endphp
                                    {{ $showDebit ? '(D)' : '(K)' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="2" style="text-align: center;">Total</td>
                            <td class="amount-col">{{ number_format($total_debet, 0, ',', '.') }}</td>
                            <td class="amount-col">{{ number_format($total_kredit, 0, ',', '.') }}</td>
                            <td class="balance-col {{ $saldo_akhir >= 0 ? 'positive-balance' : 'negative-balance' }}">
                                {{ number_format(abs($saldo_akhir), 0, ',', '.') }}
                                @php
                                    $isDebitAccount = $akun->account_position === 'debit';
                                    $isPositiveBalance = $saldo_akhir >= 0;
                                    
                                    if ($isDebitAccount) {
                                        $showDebit = $isPositiveBalance;
                                    } else {
                                        $showDebit = !$isPositiveBalance;
                                    }
                                @endphp
                                {{ $showDebit ? '(D)' : '(K)' }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endforeach
    @else
        <div class="no-data">
            <h3>Tidak ada data jurnal untuk periode {{ $bulan_nama }} {{ $tahun }}</h3>
            <p>Coba pilih periode yang berbeda atau pastikan data jurnal sudah diinput.</p>
        </div>
    @endif

<div class="footer">
    Dicetak pada: {{ \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $generated_at)->locale('id')->isoFormat('D MMMM Y') }}
<br>
    Laporan ini dibuat secara otomatis oleh sistem Koperasi Syariah
</div>



</body>
</html>
