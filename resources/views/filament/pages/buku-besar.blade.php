<x-filament::page>
    <div class="space-y-6">
        {{-- Filter Section --}}
        <x-filament::section>
            <x-slot name="heading">
                Filter Periode
            </x-slot>
            <x-slot name="description">
                Pilih periode untuk menampilkan data buku besar
            </x-slot>
            
            {{ $this->form }}
        </x-filament::section>

        {{-- Buku Besar Content Section --}}
        <x-filament::section>
            <x-slot name="heading">
                Buku Besar Perusahaan
            </x-slot>
            <x-slot name="description">
                Data buku besar periode {{ $bulan_nama }} {{ $tahun }}
                @if($entries->count() > 0)
                    - {{ $entries->count() }} akun ditemukan
                @endif
            </x-slot>

            <div class="space-y-6">
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
                        
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-6">
                            {{-- Account Header --}}
                            <div class="bg-gray-50 dark:bg-gray-800 p-4 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ $akun->account_number }} - {{ $akun->account_name }}
                                        </h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $actual_transactions->count() }} transaksi dalam periode ini
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            Saldo Akhir:
                                        </div>
                                        <div class="text-lg font-semibold {{ $saldo_akhir >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
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
                                </div>
                            </div>

                            {{-- Transaction Table --}}
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-100 dark:bg-gray-900">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Tanggal
                                            </th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Keterangan
                                            </th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Debit (Rp)
                                            </th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Kredit (Rp)
                                            </th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Saldo
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($jurnal_entries as $entry)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <td class="px-4 py-3 text-sm">
                                                    {{ $entry->keterangan == 'Saldo Awal' ? '-' : $entry->tanggal->format('d/m/Y') }}
                                                </td>
                                                <td class="px-4 py-3 text-sm">{{ $entry->keterangan }}</td>
                                                <td class="px-4 py-3 text-sm text-right">
                                                    @if($entry->keterangan != 'Saldo Awal' && $entry->debet > 0)
                                                        {{ number_format($entry->debet, 0, ',', '.') }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right">
                                                    @if($entry->keterangan != 'Saldo Awal' && $entry->kredit > 0)
                                                        {{ number_format($entry->kredit, 0, ',', '.') }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right font-medium {{ $entry->saldo >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
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
                                    <tfoot class="bg-gray-50 dark:bg-gray-900">
                                        <tr class="font-semibold">
                                            <td colspan="2" class="px-4 py-3 text-sm text-gray-900 dark:text-gray-300">
                                                Total
                                            </td>
                                            <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-gray-300">
                                                {{ number_format($total_debet, 0, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-gray-300">
                                                {{ number_format($total_kredit, 0, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-right {{ $saldo_akhir >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
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
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-8">
                        <div class="text-gray-400 dark:text-gray-500">
                            <svg class="mx-auto h-8 w-8 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="text-sm">Tidak ada data jurnal untuk periode {{ $bulan_nama }} {{ $tahun }}</span>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400 mt-1 text-sm">
                            Coba pilih periode yang berbeda atau pastikan data jurnal sudah diinput.
                        </p>
                    </div>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament::page>