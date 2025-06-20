<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filter Form -->
        <x-filament::section>
            <x-slot name="heading">
                Filter Periode
            </x-slot>
            
            <x-slot name="description">
                Pilih periode untuk menampilkan laporan laba rugi
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
                Laporan Laba Rugi
            </x-slot>
            
            <x-slot name="description">
                Periode: {{ $periode }}
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-gray-300 dark:border-gray-600">
                    <!-- Header -->
                    <thead>
                        <tr>
                            <th class="bg-blue-600 text-white px-4 py-3 text-center font-bold border border-gray-300 dark:border-gray-600" colspan="2">
                                LAPORAN LABA RUGI
                            </th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <!-- Pendapatan Section -->
                        <tr>
                            <td class="bg-blue-100 dark:bg-blue-900 px-4 py-2 font-semibold border border-gray-300 dark:border-gray-600" colspan="2">
                                PENDAPATAN
                            </td>
                        </tr>
                        
                        @forelse($pendapatan as $item)
                            <tr>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600">
                                    {{ $item->nama_akun }}
                                </td>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono font-semibold">
                                    Rp{{ number_format($item->saldo, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-400 italic">
                                    Tidak ada pendapatan
                                </td>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono text-gray-400">
                                    Rp0
                                </td>
                            </tr>
                        @endforelse
                        
                        <!-- Total Pendapatan -->
                        <tr>
                            <td class="bg-gray-100 dark:bg-gray-700 px-4 py-2 font-bold border border-gray-300 dark:border-gray-600">
                                TOTAL PENDAPATAN
                            </td>
                            <td class="bg-gray-100 dark:bg-gray-700 px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono font-bold">
                                Rp{{ number_format($total_pendapatan, 0, ',', '.') }}
                            </td>
                        </tr>
                        
                        <!-- Beban Section -->
                        <tr>
                            <td class="bg-blue-100 dark:bg-blue-900 px-4 py-2 font-semibold border border-gray-300 dark:border-gray-600" colspan="2">
                                BEBAN
                            </td>
                        </tr>
                        
                        @forelse($beban as $item)
                            <tr>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600">
                                    {{ $item->nama_akun }}
                                </td>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono font-semibold">
                                    Rp{{ number_format($item->saldo, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-400 italic">
                                    Tidak ada beban
                                </td>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono text-gray-400">
                                    Rp0
                                </td>
                            </tr>
                        @endforelse
                        
                        <!-- Total Beban -->
                        <tr>
                            <td class="bg-gray-100 dark:bg-gray-700 px-4 py-2 font-bold border border-gray-300 dark:border-gray-600">
                                TOTAL BEBAN
                            </td>
                            <td class="bg-gray-100 dark:bg-gray-700 px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono font-bold">
                                Rp{{ number_format($total_beban, 0, ',', '.') }}
                            </td>
                        </tr>
                        
                        <!-- Laba/Rugi -->
                        <tr>
                            <td class="bg-blue-600 text-white px-4 py-3 font-bold border border-gray-300 dark:border-gray-600">
                                {{ $is_profit ? 'LABA BERSIH' : 'RUGI BERSIH' }}
                            </td>
                            <td class="bg-blue-600 text-white px-4 py-3 border border-gray-300 dark:border-gray-600 text-right font-mono font-bold">
                                Rp{{ number_format(abs($laba_rugi), 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Summary Info -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
                    <h3 class="font-semibold text-green-800 dark:text-green-200">Total Pendapatan</h3>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                        Rp{{ number_format($total_pendapatan, 0, ',', '.') }}
                    </p>
                </div>
                
                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
                    <h3 class="font-semibold text-red-800 dark:text-red-200">Total Beban</h3>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                        Rp{{ number_format($total_beban, 0, ',', '.') }}
                    </p>
                </div>
                
                <div class="bg-{{ $is_profit ? 'blue' : 'orange' }}-50 dark:bg-{{ $is_profit ? 'blue' : 'orange' }}-900/20 p-4 rounded-lg border border-{{ $is_profit ? 'blue' : 'orange' }}-200 dark:border-{{ $is_profit ? 'blue' : 'orange' }}-800">
                    <h3 class="font-semibold text-{{ $is_profit ? 'blue' : 'orange' }}-800 dark:text-{{ $is_profit ? 'blue' : 'orange' }}-200">
                        {{ $is_profit ? 'Laba Bersih' : 'Rugi Bersih' }}
                    </h3>
                    <p class="text-2xl font-bold text-{{ $is_profit ? 'blue' : 'orange' }}-600 dark:text-{{ $is_profit ? 'blue' : 'orange' }}-400">
                        Rp{{ number_format(abs($laba_rugi), 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
