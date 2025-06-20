<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filter Form -->
        <x-filament::section>
            <x-slot name="heading">
                Filter Periode
            </x-slot>
            
            <x-slot name="description">
                Pilih periode untuk menampilkan laporan arus kas
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
                Laporan Arus Kas
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
                                LAPORAN ARUS KAS
                            </th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <!-- Kas Awal -->
                        <tr>
                            <td class="bg-gray-100 dark:bg-gray-700 px-4 py-2 font-bold border border-gray-300 dark:border-gray-600">
                                Kas dan Setara Kas Awal Periode
                            </td>
                            <td class="bg-gray-100 dark:bg-gray-700 px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono font-bold">
                                Rp{{ number_format($kas_awal, 0, ',', '.') }}
                            </td>
                        </tr>
                        
                        <!-- Arus Kas dari Aktivitas Operasi -->
                        <tr>
                            <td class="bg-blue-100 dark:bg-blue-900 px-4 py-2 font-semibold border border-gray-300 dark:border-gray-600" colspan="2">
                                ARUS KAS DARI AKTIVITAS OPERASI
                            </td>
                        </tr>
                        
                        @forelse($arus_operasi as $item)
                            <tr>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600">
                                    {{ $item->keterangan }}
                                </td>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono font-semibold {{ $item->type == 'masuk' ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $item->type == 'masuk' ? '+' : '-' }}Rp{{ number_format($item->jumlah, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-400 italic">
                                    Tidak ada aktivitas operasi
                                </td>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono text-gray-400">
                                    Rp0
                                </td>
                            </tr>
                        @endforelse
                        
                        <!-- Total Operasi -->
                        <tr>
                            <td class="bg-gray-100 dark:bg-gray-700 px-4 py-2 font-bold border border-gray-300 dark:border-gray-600">
                                Arus Kas Bersih dari Aktivitas Operasi
                            </td>
                            <td class="bg-gray-100 dark:bg-gray-700 px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono font-bold {{ $total_operasi >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $total_operasi >= 0 ? '+' : '' }}Rp{{ number_format($total_operasi, 0, ',', '.') }}
                            </td>
                        </tr>
                        
                        <!-- Arus Kas dari Aktivitas Investasi -->
                        <tr>
                            <td class="bg-blue-100 dark:bg-blue-900 px-4 py-2 font-semibold border border-gray-300 dark:border-gray-600" colspan="2">
                                ARUS KAS DARI AKTIVITAS INVESTASI
                            </td>
                        </tr>
                        
                        @forelse($arus_investasi as $item)
                            <tr>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600">
                                    {{ $item->keterangan }}
                                </td>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono font-semibold {{ $item->type == 'masuk' ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $item->type == 'masuk' ? '+' : '-' }}Rp{{ number_format($item->jumlah, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-400 italic">
                                    Tidak ada aktivitas investasi
                                </td>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono text-gray-400">
                                    Rp0
                                </td>
                            </tr>
                        @endforelse
                        
                        <!-- Total Investasi -->
                        <tr>
                            <td class="bg-gray-100 dark:bg-gray-700 px-4 py-2 font-bold border border-gray-300 dark:border-gray-600">
                                Arus Kas Bersih dari Aktivitas Investasi
                            </td>
                            <td class="bg-gray-100 dark:bg-gray-700 px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono font-bold {{ $total_investasi >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $total_investasi >= 0 ? '+' : '' }}Rp{{ number_format($total_investasi, 0, ',', '.') }}
                            </td>
                        </tr>
                        
                        <!-- Arus Kas dari Aktivitas Pendanaan -->
                        <tr>
                            <td class="bg-blue-100 dark:bg-blue-900 px-4 py-2 font-semibold border border-gray-300 dark:border-gray-600" colspan="2">
                                ARUS KAS DARI AKTIVITAS PENDANAAN
                            </td>
                        </tr>
                        
                        @forelse($arus_pendanaan as $item)
                            <tr>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600">
                                    {{ $item->keterangan }}
                                </td>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono font-semibold {{ $item->type == 'masuk' ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $item->type == 'masuk' ? '+' : '-' }}Rp{{ number_format($item->jumlah, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-400 italic">
                                    Tidak ada aktivitas pendanaan
                                </td>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono text-gray-400">
                                    Rp0
                                </td>
                            </tr>
                        @endforelse
                        
                        <!-- Total Pendanaan -->
                        <tr>
                            <td class="bg-gray-100 dark:bg-gray-700 px-4 py-2 font-bold border border-gray-300 dark:border-gray-600">
                                Arus Kas Bersih dari Aktivitas Pendanaan
                            </td>
                            <td class="bg-gray-100 dark:bg-gray-700 px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono font-bold {{ $total_pendanaan >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $total_pendanaan >= 0 ? '+' : '' }}Rp{{ number_format($total_pendanaan, 0, ',', '.') }}
                            </td>
                        </tr>
                        
                        <!-- Arus Kas Bersih -->
                        <tr>
                            <td class="bg-yellow-100 dark:bg-yellow-900 px-4 py-2 font-bold border border-gray-300 dark:border-gray-600">
                                Kenaikan (Penurunan) Bersih Kas dan Setara Kas
                            </td>
                            <td class="bg-yellow-100 dark:bg-yellow-900 px-4 py-2 border border-gray-300 dark:border-gray-600 text-right font-mono font-bold {{ $arus_kas_bersih >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $arus_kas_bersih >= 0 ? '+' : '' }}Rp{{ number_format($arus_kas_bersih, 0, ',', '.') }}
                            </td>
                        </tr>
                        
                        <!-- Kas Akhir -->
                        <tr>
                            <td class="bg-blue-600 text-white px-4 py-3 font-bold border border-gray-300 dark:border-gray-600">
                                Kas dan Setara Kas Akhir Periode
                            </td>
                            <td class="bg-blue-600 text-white px-4 py-3 border border-gray-300 dark:border-gray-600 text-right font-mono font-bold">
                                Rp{{ number_format($kas_akhir, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Summary Info -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                    <h3 class="font-semibold text-blue-800 dark:text-blue-200">Kas Awal</h3>
                    <p class="text-xl font-bold text-blue-600 dark:text-blue-400">
                        Rp{{ number_format($kas_awal, 0, ',', '.') }}
                    </p>
                </div>
                
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
                    <h3 class="font-semibold text-green-800 dark:text-green-200">Operasi</h3>
                    <p class="text-xl font-bold {{ $total_operasi >= 0 ? 'text-green-600' : 'text-red-600' }} dark:text-green-400">
                        {{ $total_operasi >= 0 ? '+' : '' }}Rp{{ number_format($total_operasi, 0, ',', '.') }}
                    </p>
                </div>
                
                <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
                    <h3 class="font-semibold text-purple-800 dark:text-purple-200">Investasi</h3>
                    <p class="text-xl font-bold {{ $total_investasi >= 0 ? 'text-green-600' : 'text-red-600' }} dark:text-purple-400">
                        {{ $total_investasi >= 0 ? '+' : '' }}Rp{{ number_format($total_investasi, 0, ',', '.') }}
                    </p>
                </div>
                
                <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg border border-orange-200 dark:border-orange-800">
                    <h3 class="font-semibold text-orange-800 dark:text-orange-200">Kas Akhir</h3>
                    <p class="text-xl font-bold text-orange-600 dark:text-orange-400">
                        Rp{{ number_format($kas_akhir, 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
