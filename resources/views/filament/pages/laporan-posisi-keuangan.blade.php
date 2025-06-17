<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filter Form -->
        <x-filament::section>
            <x-slot name="heading">
                Filter Periode
            </x-slot>
            
            <x-slot name="description">
                Pilih periode untuk menampilkan laporan posisi keuangan
            </x-slot>

            {{ $this->form }}
        </x-filament::section>

        <!-- Laporan Posisi Keuangan Content -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex justify-between items-center w-full">
                    <div>
                        <h3 class="text-lg font-semibold">LAPORAN POSISI KEUANGAN</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Periode: {{ $periode }} | Dicetak: {{ $tanggal_cetak }}
                        </p>
                    </div>
                    <div class="text-right">
                        @if($is_balanced)
                            <x-filament::badge color="success">
                                Posisi Seimbang
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
                <table class="w-full border-collapse border border-gray-300 dark:border-gray-600">
                    <!-- Header Row -->
                    <thead>
                        <tr>
                            <th class="w-1/2 bg-blue-600 text-white px-4 py-3 text-center font-bold text-lg border border-gray-300 dark:border-gray-600">
                                AKTIVA
                            </th>
                            <th class="w-1/2 bg-blue-600 text-white px-4 py-3 text-center font-bold text-lg border border-gray-300 dark:border-gray-600">
                                PASIVA
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Aktiva & Pasiva Category Headers -->
                        <tr>
                            <td class="bg-gray-200 dark:bg-gray-700 px-4 py-2 font-bold text-center border border-gray-300 dark:border-gray-600">
                                Aktiva
                            </td>
                            <td class="bg-gray-200 dark:bg-gray-700 px-4 py-2 font-bold text-center border border-gray-300 dark:border-gray-600">
                                Pasiva
                            </td>
                        </tr>

                        <!-- Aktiva Lancar & Kewajiban Headers -->
                        <tr>
                            <td class="bg-blue-100 dark:bg-blue-900 px-4 py-2 font-semibold border border-gray-300 dark:border-gray-600">
                                Aktiva Lancar
                            </td>
                            <td class="bg-blue-100 dark:bg-blue-900 px-4 py-2 font-semibold border border-gray-300 dark:border-gray-600">
                                Kewajiban
                            </td>
                        </tr>

                        <!-- Content Rows -->
                        @php
                            $maxRows = max($aktiva_lancar->count(), $kewajiban->count());
                        @endphp

                        @for($i = 0; $i < $maxRows; $i++)
                            <tr>
                                <!-- Aktiva Lancar Item -->
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600">
                                    @if($aktiva_lancar->has($i))
                                        <div class="flex justify-between">
                                            <span>{{ $aktiva_lancar[$i]->nama_akun }}</span>
                                            <span class="font-mono font-semibold">
                                                Rp{{ number_format($aktiva_lancar[$i]->saldo, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    @else
                                        <div class="flex justify-between">
                                            <span class="text-gray-400 italic">Aktiva Lancar Lainnya</span>
                                            <span class="text-gray-400 font-mono">Rp0</span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Kewajiban Item -->
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600">
                                    @if($kewajiban->has($i))
                                        <div class="flex justify-between">
                                            <span>{{ $kewajiban[$i]->nama_akun }}</span>
                                            <span class="font-mono font-semibold">
                                                Rp{{ number_format($kewajiban[$i]->saldo, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    @else
                                        <div class="flex justify-between">
                                            <span class="text-gray-400 italic">Kewajiban Lancar Lainnya</span>
                                            <span class="text-gray-400 font-mono">Rp0</span>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endfor

                        <!-- Subtotal Row -->
                        <tr>
                            <td class="px-4 py-2 bg-gray-100 dark:bg-gray-800 font-bold border border-gray-300 dark:border-gray-600">
                                <div class="flex justify-between">
                                    <span>TOTAL</span>
                                    <span class="font-mono">Rp{{ number_format($total_aktiva_lancar, 0, ',', '.') }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-2 bg-gray-100 dark:bg-gray-800 font-bold border border-gray-300 dark:border-gray-600">
                                <div class="flex justify-between">
                                    <span>TOTAL</span>
                                    <span class="font-mono">Rp{{ number_format($total_kewajiban, 0, ',', '.') }}</span>
                                </div>
                            </td>
                        </tr>

                        <!-- Aktiva Tetap & Ekuitas Headers -->
                        <tr>
                            <td class="bg-blue-100 dark:bg-blue-900 px-4 py-2 font-semibold border border-gray-300 dark:border-gray-600">
                                Aktiva Tetap
                            </td>
                            <td class="bg-blue-100 dark:bg-blue-900 px-4 py-2 font-semibold border border-gray-300 dark:border-gray-600">
                                Ekuitas
                            </td>
                        </tr>

                        <!-- Aktiva Tetap & Ekuitas Content -->
                        @php
                            $maxRows2 = max($aktiva_tetap->count(), $ekuitas->count());
                        @endphp

                        @for($i = 0; $i < $maxRows2; $i++)
                            <tr>
                                <!-- Aktiva Tetap Item -->
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600">
                                    @if($aktiva_tetap->has($i))
                                        <div class="flex justify-between">
                                            <span>{{ $aktiva_tetap[$i]->nama_akun }}</span>
                                            <span class="font-mono font-semibold">
                                                Rp{{ number_format($aktiva_tetap[$i]->saldo, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    @else
                                        <div class="flex justify-between">
                                            <span class="text-gray-400 italic">Inventaris</span>
                                            <span class="text-gray-400 font-mono">Rp0</span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Ekuitas Item -->
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600">
                                    @if($ekuitas->has($i))
                                        <div class="flex justify-between">
                                            <span>{{ $ekuitas[$i]->nama_akun }}</span>
                                            <span class="font-mono font-semibold">
                                                Rp{{ number_format($ekuitas[$i]->saldo, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    @else
                                        <div class="flex justify-between">
                                            <span class="text-gray-400 italic">Prive</span>
                                            <span class="text-gray-400 font-mono">Rp0</span>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endfor

                        <!-- Subtotal Row 2 -->
                        <tr>
                            <td class="px-4 py-2 bg-gray-100 dark:bg-gray-800 font-bold border border-gray-300 dark:border-gray-600">
                                <div class="flex justify-between">
                                    <span>TOTAL</span>
                                    <span class="font-mono">Rp{{ number_format($total_aktiva_tetap, 0, ',', '.') }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-2 bg-gray-100 dark:bg-gray-800 font-bold border border-gray-300 dark:border-gray-600">
                                <div class="flex justify-between">
                                    <span>TOTAL</span>
                                    <span class="font-mono">Rp{{ number_format($total_ekuitas, 0, ',', '.') }}</span>
                                </div>
                            </td>
                        </tr>

                        <!-- Grand Total Row -->
                        <tr>
                            <td class="bg-blue-600 text-white px-4 py-3 font-bold text-lg border border-gray-300 dark:border-gray-600">
                                <div class="flex justify-between">
                                    <span>TOTAL AKTIVA</span>
                                    <span class="font-mono">Rp{{ number_format($total_aktiva, 0, ',', '.') }}</span>
                                </div>
                            </td>
                            <td class="bg-blue-600 text-white px-4 py-3 font-bold text-lg border border-gray-300 dark:border-gray-600">
                                <div class="flex justify-between">
                                    <span>TOTAL PASIVA</span>
                                    <span class="font-mono">Rp{{ number_format($total_pasiva, 0, ',', '.') }}</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Summary Information -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center space-x-2">
                            <x-heroicon-o-building-office class="w-5 h-5 text-blue-500" />
                            <span>Total Aktiva</span>
                        </div>
                    </x-slot>
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 font-mono">
                        Rp{{ number_format($total_aktiva, 0, ',', '.') }}
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center space-x-2">
                            <x-heroicon-o-building-office-2 class="w-5 h-5 text-green-500" />
                            <span>Total Pasiva</span>
                        </div>
                    </x-slot>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400 font-mono">
                        Rp{{ number_format($total_pasiva, 0, ',', '.') }}
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center space-x-2">
                            <x-heroicon-o-scale class="w-5 h-5 {{ $is_balanced ? 'text-green-500' : 'text-red-500' }}" />
                            <span>Status Posisi</span>
                        </div>
                    </x-slot>
                    <div class="space-y-2">
                        @if($is_balanced)
                            <div class="text-lg font-bold text-green-600 dark:text-green-400">
                                SEIMBANG
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                Posisi keuangan balance
                            </div>
                        @else
                            <div class="text-lg font-bold text-red-600 dark:text-red-400">
                                TIDAK SEIMBANG
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                Selisih: Rp{{ number_format($selisih, 0, ',', '.') }}
                            </div>
                        @endif
                    </div>
                </x-filament::section>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
