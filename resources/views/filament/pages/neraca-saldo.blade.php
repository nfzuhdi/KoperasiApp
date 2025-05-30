<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filter Form -->
        <x-filament::section>
            <x-slot name="heading">
                Filter Periode
            </x-slot>
            
            <x-slot name="description">
                Pilih periode untuk menampilkan neraca saldo
            </x-slot>

            {{ $this->form }}
        </x-filament::section>

        <!-- Neraca Saldo Content -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex justify-between items-center w-full">
                    <div>
                        <h3 class="text-lg font-semibold">Neraca Saldo</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Periode: {{ $periode }} | Dicetak: {{ $tanggal_cetak }}
                        </p>
                    </div>
                    <div class="text-right">
                        @if($is_balanced)
                            <x-filament::badge color="success">
                                Neraca Seimbang
                            </x-filament::badge>
                        @else
                            <x-filament::badge color="danger">
                                Selisih: {{ number_format($selisih, 0, ',', '.') }}
                            </x-filament::badge>
                        @endif
                    </div>
                </div>
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full table-auto border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">
                                Kode Akun
                            </th>
                            <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">
                                Nama Akun
                            </th>
                            <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center font-semibold text-gray-900 dark:text-gray-100">
                                Posisi Normal
                            </th>
                            <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                Debet
                            </th>
                            <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                Kredit
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($neraca_saldo as $index => $item)
                            <tr class="{{ $index % 2 == 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50 dark:bg-gray-800' }}">
                                <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-sm font-mono">
                                    {{ $item->kode_akun }}
                                </td>
                                <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-sm">
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $item->nama_akun }}
                                        </div>
                                        @if($item->account_type)
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $item->account_type }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center text-sm">
                                    <x-filament::badge 
                                        :color="$item->posisi_normal === 'Debit' ? 'info' : 'warning'"
                                        size="sm"
                                    >
                                        {{ $item->posisi_normal }}
                                    </x-filament::badge>
                                </td>
                                <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right text-sm font-mono">
                                    @if($item->saldo_debet > 0)
                                        <span class="text-gray-900 dark:text-gray-100 font-semibold">
                                            {{ number_format($item->saldo_debet, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">-</span>
                                    @endif
                                </td>
                                <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right text-sm font-mono">
                                    @if($item->saldo_kredit > 0)
                                        <span class="text-gray-900 dark:text-gray-100 font-semibold">
                                            {{ number_format($item->saldo_kredit, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="border border-gray-300 dark:border-gray-600 px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center space-y-2">
                                        <x-heroicon-o-document-text class="w-12 h-12 text-gray-400" />
                                        <p>Tidak ada data neraca saldo untuk periode ini</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    
                    <!-- Total Row -->
                    @if($neraca_saldo->isNotEmpty())
                        <tfoot>
                            <tr class="bg-gray-100 dark:bg-gray-700 font-bold">
                                <td colspan="3" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right font-bold text-gray-900 dark:text-gray-100">
                                    TOTAL
                                </td>
                                <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right font-bold text-gray-900 dark:text-gray-100 font-mono">
                                    {{ number_format($total_debet, 0, ',', '.') }}
                                </td>
                                <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right font-bold text-gray-900 dark:text-gray-100 font-mono">
                                    {{ number_format($total_kredit, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <!-- Summary Information -->
            @if($neraca_saldo->isNotEmpty())
                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-filament::section>
                        <x-slot name="heading">
                            <div class="flex items-center space-x-2">
                                <x-heroicon-o-banknotes class="w-5 h-5 text-blue-500" />
                                <span>Total Debet</span>
                            </div>
                        </x-slot>
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 font-mono">
                            {{ number_format($total_debet, 0, ',', '.') }}
                        </div>
                    </x-filament::section>

                    <x-filament::section>
                        <x-slot name="heading">
                            <div class="flex items-center space-x-2">
                                <x-heroicon-o-banknotes class="w-5 h-5 text-green-500" />
                                <span>Total Kredit</span>
                            </div>
                        </x-slot>
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400 font-mono">
                            {{ number_format($total_kredit, 0, ',', '.') }}
                        </div>
                    </x-filament::section>

                    <x-filament::section>
                        <x-slot name="heading">
                            <div class="flex items-center space-x-2">
                                <x-heroicon-o-scale class="w-5 h-5 {{ $is_balanced ? 'text-green-500' : 'text-red-500' }}" />
                                <span>Status Neraca</span>
                            </div>
                        </x-slot>
                        <div class="space-y-2">
                            @if($is_balanced)
                                <div class="text-lg font-bold text-green-600 dark:text-green-400">
                                    SEIMBANG
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    Neraca sudah balance
                                </div>
                            @else
                                <div class="text-lg font-bold text-red-600 dark:text-red-400">
                                    TIDAK SEIMBANG
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    Selisih: {{ number_format($selisih, 0, ',', '.') }}
                                </div>
                            @endif
                        </div>
                    </x-filament::section>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>