<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filter Form -->
        <x-filament::section>
            <x-slot name="heading">
                Filter Periode
            </x-slot>
            
            <x-slot name="description">
                Pilih periode untuk menampilkan laporan perubahan ekuitas
            </x-slot>

            {{ $this->form }}
        </x-filament::section>

        @php
            $viewData = $this->getViewData();
            extract($viewData);
        @endphp

        <!-- Laporan Content -->
        <x-filament::section>
            <x-slot name="heading">
                Laporan Perubahan Ekuitas
            </x-slot>
            
            <x-slot name="description">
                Periode: {{ $periode }}
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-gray-300 dark:border-gray-600">
                    <!-- Header -->
                    <thead>
                        <tr>
                            <th class="bg-blue-600 text-white px-4 py-3 text-center font-bold border border-gray-300 dark:border-gray-600" colspan="4">
                                LAPORAN PERUBAHAN EKUITAS
                            </th>
                        </tr>
                        <tr>
                            <th class="bg-blue-100 dark:bg-blue-900 px-4 py-2 font-semibold border border-gray-300 dark:border-gray-600">
                                KETERANGAN
                            </th>
                            <th class="bg-blue-100 dark:bg-blue-900 px-4 py-2 font-semibold border border-gray-300 dark:border-gray-600 text-center">
                                SALDO AWAL
                            </th>
                            <th class="bg-blue-100 dark:bg-blue-900 px-4 py-2 font-semibold border border-gray-300 dark:border-gray-600 text-center">
                                PERUBAHAN
                            </th>
                            <th class="bg-blue-100 dark:bg-blue-900 px-4 py-2 font-semibold border border-gray-300 dark:border-gray-600 text-center">
                                SALDO AKHIR
                            </th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        @forelse($ekuitas as $item)
                            <tr>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600">
                                    {{ $item->nama_akun }}
                                </td>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono font-semibold">
                                    Rp{{ number_format($item->saldo_awal, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono font-semibold {{ $item->perubahan >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $item->perubahan >= 0 ? '+' : '' }}Rp{{ number_format($item->perubahan, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono font-semibold">
                                    Rp{{ number_format($item->saldo_akhir, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-400 italic" colspan="4">
                                    Tidak ada data ekuitas
                                </td>
                            </tr>
                        @endforelse
                        
                        <!-- Laba/Rugi Periode Berjalan -->
                        <tr>
                            <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 font-semibold">
                                {{ $is_profit ? 'Laba' : 'Rugi' }} Periode Berjalan
                            </td>
                            <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono">
                                Rp0
                            </td>
                            <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono font-semibold {{ $is_profit ? 'text-green-600' : 'text-red-600' }}">
                                {{ $is_profit ? '+' : '' }}Rp{{ number_format($laba_rugi, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono font-semibold {{ $is_profit ? 'text-green-600' : 'text-red-600' }}">
                                Rp{{ number_format(abs($laba_rugi), 0, ',', '.') }}
                            </td>
                        </tr>
                        
                        <!-- Total -->
                        <tr>
                            <td class="bg-blue-600 text-white px-4 py-3 font-bold border border-gray-300 dark:border-gray-600">
                                TOTAL EKUITAS
                            </td>
                            <td class="bg-blue-600 text-white px-4 py-3 border border-gray-300 dark:border-gray-600 text-right font-mono font-bold">
                                Rp{{ number_format($total_ekuitas_awal, 0, ',', '.') }}
                            </td>
                            <td class="bg-blue-600 text-white px-4 py-3 border border-gray-300 dark:border-gray-600 text-right font-mono font-bold">
                                {{ $total_perubahan + $laba_rugi >= 0 ? '+' : '' }}Rp{{ number_format($total_perubahan + $laba_rugi, 0, ',', '.') }}
                            </td>
                            <td class="bg-blue-600 text-white px-4 py-3 border border-gray-300 dark:border-gray-600 text-right font-mono font-bold">
                                Rp{{ number_format($total_ekuitas_akhir + abs($laba_rugi), 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Summary Info -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                    <h3 class="font-semibold text-blue-800 dark:text-blue-200">Ekuitas Awal</h3>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        Rp{{ number_format($total_ekuitas_awal, 0, ',', '.') }}
                    </p>
                </div>
                
                <div class="bg-{{ $is_profit ? 'green' : 'red' }}-50 dark:bg-{{ $is_profit ? 'green' : 'red' }}-900/20 p-4 rounded-lg border border-{{ $is_profit ? 'green' : 'red' }}-200 dark:border-{{ $is_profit ? 'green' : 'red' }}-800">
                    <h3 class="font-semibold text-{{ $is_profit ? 'green' : 'red' }}-800 dark:text-{{ $is_profit ? 'green' : 'red' }}-200">
                        {{ $is_profit ? 'Laba' : 'Rugi' }} Periode
                    </h3>
                    <p class="text-2xl font-bold text-{{ $is_profit ? 'green' : 'red' }}-600 dark:text-{{ $is_profit ? 'green' : 'red' }}-400">
                        Rp{{ number_format(abs($laba_rugi), 0, ',', '.') }}
                    </p>
                </div>
                
                <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
                    <h3 class="font-semibold text-purple-800 dark:text-purple-200">Ekuitas Akhir</h3>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                        Rp{{ number_format($total_ekuitas_akhir + abs($laba_rugi), 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
