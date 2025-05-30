<x-filament::page>
    <div class="space-y-6">
        {{-- Filter Section --}}
        <x-filament::section>
            <x-slot name="heading">Filter Periode</x-slot>
            <x-slot name="description">Pilih rentang tanggal untuk menampilkan data jurnal umum</x-slot>
            
            {{ $this->form }}
        </x-filament::section>

        {{-- Content Section --}}
        <x-filament::section>
            <x-slot name="heading">Jurnal Umum</x-slot>
            <x-slot name="description">
                Data jurnal umum periode: {{ $periode }}
                @if($records->count() > 0)
                    - {{ $records->groupBy('no_transaksi')->count() }} transaksi ditemukan
                @endif
            </x-slot>

            <div class="space-y-6">
                @php
                    $groupedRecords = $records->groupBy('no_transaksi');
                @endphp

                @forelse($groupedRecords as $noTransaksi => $entries)
                    @php
                        $totalDebet = $entries->sum('debet');
                        $totalKredit = $entries->sum('kredit');
                        $isBalanced = $totalDebet == $totalKredit;
                    @endphp
                    
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-6">
                        {{-- Header Transaksi --}}
                        <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                        No Transaksi: {{ $noTransaksi }}
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        Tanggal: {{ $entries->first()->tanggal_bayar->format('d F Y') }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm {{ $isBalanced ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $isBalanced ? '✓ Seimbang' : '⚠ Tidak Seimbang' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-100 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Kode Akun
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Nama Akun
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
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($entries as $entry)
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-300">
                                                {{ $entry->akun->account_number }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-300">
                                                {{ $entry->akun->account_name }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-300">
                                                {{ $entry->keterangan }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">
                                                @if($entry->debet > 0)
                                                    <span class="font-medium">{{ number_format($entry->debet, 0, ',', '.') }}</span>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">
                                                @if($entry->kredit > 0)
                                                    <span class="font-medium">{{ number_format($entry->kredit, 0, ',', '.') }}</span>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50 dark:bg-gray-900">
                                    <tr class="font-semibold">
                                        <td colspan="3" class="px-4 py-3 text-sm text-gray-900 dark:text-gray-300">
                                            Total
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right {{ $totalDebet > 0 ? 'text-gray-900 dark:text-gray-300' : 'text-gray-400' }}">
                                            {{ $totalDebet > 0 ? number_format($totalDebet, 0, ',', '.') : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right {{ $totalKredit > 0 ? 'text-gray-900 dark:text-gray-300' : 'text-gray-400' }}">
                                            {{ $totalKredit > 0 ? number_format($totalKredit, 0, ',', '.') : '-' }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <div class="text-gray-400 dark:text-gray-500 text-lg">
                            <svg class="mx-auto h-12 w-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Tidak ada data jurnal untuk periode {{ $periode }}
                        </div>
                        <p class="text-gray-500 dark:text-gray-400 mt-2">
                            Coba pilih rentang tanggal yang berbeda atau pastikan data jurnal sudah diinput.
                        </p>
                    </div>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament::page>