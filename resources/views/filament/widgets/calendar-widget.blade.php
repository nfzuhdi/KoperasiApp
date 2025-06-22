<x-filament::section>
    <x-slot name="heading">
        Kalender {{ $currentMonth }}
    </x-slot>

    <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800">
        <div class="grid grid-cols-7 gap-px border-b border-gray-300 dark:border-gray-700 bg-gray-200 dark:bg-gray-700 text-xs">
            <div class="text-center font-medium py-2 text-gray-700 dark:text-gray-300">Min</div>
            <div class="text-center font-medium py-2 text-gray-700 dark:text-gray-300">Sen</div>
            <div class="text-center font-medium py-2 text-gray-700 dark:text-gray-300">Sel</div>
            <div class="text-center font-medium py-2 text-gray-700 dark:text-gray-300">Rab</div>
            <div class="text-center font-medium py-2 text-gray-700 dark:text-gray-300">Kam</div>
            <div class="text-center font-medium py-2 text-gray-700 dark:text-gray-300">Jum</div>
            <div class="text-center font-medium py-2 text-gray-700 dark:text-gray-300">Sab</div>
        </div>
        
        <div class="bg-white dark:bg-gray-800">
            @foreach ($weeks as $week)
                <div class="grid grid-cols-7 gap-px border-b border-gray-200 dark:border-gray-700">
                    @foreach ($week as $day)
                        <div class="min-h-[3rem] py-2 px-3 {{ $day['isCurrentMonth'] ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400' }} {{ $day['isToday'] ? 'bg-primary-50 dark:bg-primary-900/20 font-bold' : '' }}">
                            <div class="text-right text-sm {{ $day['isToday'] ? 'text-primary-600 dark:text-primary-400' : '' }}">
                                {{ $day['date']->format('j') }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</x-filament::section>