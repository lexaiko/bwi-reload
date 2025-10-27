<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Header dengan filter --}}
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white">
                    Transaksi Per Minggu
                </h3>
            </div>

            {{-- Form Filter --}}
            <div class="mb-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{ $this->form }}
                </div>
            </div>
        </div>

        {{-- Stats Cards - 2 Baris Layout --}}
        <div class="space-y-4">
            {{-- Baris 1: Minggu 1, 2, 3, 4 --}}
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                @foreach ($this->getStats() as $stat)
                    @if(!($stat['is_total'] ?? false))
                        <x-filament::card class="p-3 shadow-sm hover:shadow-md transition-shadow duration-200">
                            {{-- Icon & Title --}}
                            <div class="flex items-center mb-2">
                                <div class="flex-shrink-0 mr-2">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg {{
                                        $stat['color'] === 'success' ? 'bg-success-100 dark:bg-success-900/50' :
                                        ($stat['color'] === 'warning' ? 'bg-warning-100 dark:bg-warning-900/50' :
                                        ($stat['color'] === 'danger' ? 'bg-danger-100 dark:bg-danger-900/50' : 'bg-primary-100 dark:bg-primary-900/50'))
                                    }}">
                                        <x-heroicon-o-banknotes class="w-4 h-4 {{
                                            $stat['color'] === 'success' ? 'text-success-600 dark:text-success-400' :
                                            ($stat['color'] === 'warning' ? 'text-warning-600 dark:text-warning-400' :
                                            ($stat['color'] === 'danger' ? 'text-danger-600 dark:text-danger-400' : 'text-primary-600 dark:text-primary-400'))
                                        }}"/>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-xs font-medium text-gray-600 dark:text-gray-400 truncate">
                                        {{ $stat['title'] }}
                                    </h4>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">
                                        {{ $stat['value'] }}
                                    </p>
                                </div>
                            </div>

                            {{-- Details --}}
                            <div class="space-y-1 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Bayar:</span>
                                    <span class="font-medium text-success-600 dark:text-success-400">
                                        Rp {{ $stat['description']['bayar'] }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Minus:</span>
                                    <span class="font-medium text-danger-600 dark:text-danger-400">
                                        Rp {{ $stat['description']['minus'] }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Sisa:</span>
                                    <span class="font-medium {{
                                        $stat['color'] === 'success' ? 'text-success-600 dark:text-success-400' :
                                        ($stat['color'] === 'warning' ? 'text-warning-600 dark:text-warning-400' :
                                        ($stat['color'] === 'danger' ? 'text-danger-600 dark:text-danger-400' : 'text-primary-600 dark:text-primary-400'))
                                    }}">
                                        Rp {{ $stat['description']['sisa'] }}
                                    </span>
                                </div>
                            </div>

                            {{-- Mini Chart Per Hari --}}
                            @if(!empty($stat['chart']))
                                <div class="mt-3 pt-2 border-t border-gray-200 dark:border-gray-700">
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Transaksi per hari</div>
                                    <div class="flex items-end space-x-1 h-12 bg-gray-50 dark:bg-gray-800 rounded p-1">
                                        @php
                                            $maxValue = max($stat['chart']) ?: 1;
                                        @endphp
                                        @foreach($stat['chart'] as $index => $value)
                                            <div class="flex-1 flex flex-col items-center">
                                                <div class="w-full flex justify-center mb-1">
                                                    <div
                                                        class="w-3 rounded-t {{
                                                            $stat['color'] === 'success' ? 'bg-success-500' :
                                                            ($stat['color'] === 'warning' ? 'bg-warning-500' :
                                                            ($stat['color'] === 'danger' ? 'bg-danger-500' : 'bg-primary-500'))
                                                        }} border border-gray-300"
                                                        style="height: {{ $value > 0 ? max(4, ($value / $maxValue) * 36) : 2 }}px"
                                                        title="{{ ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$index] ?? '' }}: Rp {{ number_format($value, 0, ',', '.') }}">
                                                    </div>
                                                </div>
                                                <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">
                                                    {{ ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'][$index] ?? '' }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </x-filament::card>
                    @endif
                @endforeach
            </div>

            {{-- Baris 2: Total Transaksi Bulan --}}
            @foreach ($this->getStats() as $stat)
                @if($stat['is_total'] ?? false)
                    <div class="grid gap-4">
                        <x-filament::card class="p-4 shadow-sm hover:shadow-md transition-shadow duration-200">
                            {{-- Header Total --}}
                            <div class="flex items-start mb-4">
                                <div class="flex-shrink-0 mr-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg {{
                                        $stat['color'] === 'success' ? 'bg-success-100 dark:bg-success-900/50' :
                                        ($stat['color'] === 'warning' ? 'bg-warning-100 dark:bg-warning-900/50' :
                                        ($stat['color'] === 'danger' ? 'bg-danger-100 dark:bg-danger-900/50' : 'bg-primary-100 dark:bg-primary-900/50'))
                                    }}">
                                        <x-heroicon-o-chart-bar class="w-5 h-5 {{
                                            $stat['color'] === 'success' ? 'text-success-600 dark:text-success-400' :
                                            ($stat['color'] === 'warning' ? 'text-warning-600 dark:text-warning-400' :
                                            ($stat['color'] === 'danger' ? 'text-danger-600 dark:text-danger-400' : 'text-primary-600 dark:text-primary-400'))
                                        }}"/>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white truncate">
                                        {{ $stat['title'] }}
                                    </h3>
                                    <p class="text-sm font-bold {{
                                        $stat['color'] === 'success' ? 'text-success-600 dark:text-success-400' :
                                        ($stat['color'] === 'warning' ? 'text-warning-600 dark:text-warning-400' :
                                        ($stat['color'] === 'danger' ? 'text-danger-600 dark:text-danger-400' : 'text-primary-600 dark:text-primary-400'))
                                    }} mt-0.5">
                                        {{ $stat['value'] }}
                                    </p>
                                </div>
                            </div>

                            {{-- Total Details dalam Grid --}}
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                                <div class="text-center p-2 rounded-lg bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-800">
                                    <div class="text-xs font-medium text-success-600 dark:text-success-400 mb-0.5">Total Bayar</div>
                                    <div class="text-sm font-bold text-success-700 dark:text-success-300">
                                        Rp {{ $stat['description']['bayar'] }}
                                    </div>
                                </div>
                                <div class="text-center p-2 rounded-lg bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800">
                                    <div class="text-xs font-medium text-danger-600 dark:text-danger-400 mb-0.5">Total Minus</div>
                                    <div class="text-sm font-bold text-danger-700 dark:text-danger-300">
                                        Rp {{ $stat['description']['minus'] }}
                                    </div>
                                </div>
                                <div class="text-center p-2 rounded-lg {{
                                    $stat['color'] === 'success' ? 'bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-800' :
                                    ($stat['color'] === 'warning' ? 'bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800' :
                                    ($stat['color'] === 'danger' ? 'bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800' : 'bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800'))
                                }}">
                                    <div class="text-xs font-medium {{
                                        $stat['color'] === 'success' ? 'text-success-600 dark:text-success-400' :
                                        ($stat['color'] === 'warning' ? 'text-warning-600 dark:text-warning-400' :
                                        ($stat['color'] === 'danger' ? 'text-danger-600 dark:text-danger-400' : 'text-primary-600 dark:text-primary-400'))
                                    }} mb-0.5">Total Sisa</div>
                                    <div class="text-sm font-bold {{
                                        $stat['color'] === 'success' ? 'text-success-700 dark:text-success-300' :
                                        ($stat['color'] === 'warning' ? 'text-warning-700 dark:text-warning-300' :
                                        ($stat['color'] === 'danger' ? 'text-danger-700 dark:text-danger-300' : 'text-primary-700 dark:text-primary-300'))
                                    }}">
                                        Rp {{ $stat['description']['sisa'] }}
                                    </div>
                                </div>
                            </div>

                            {{-- Chart Per Minggu --}}
                            @if(!empty($stat['chart']))
                                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-3">Grafik Transaksi Per Minggu</h4>
                                    <div class="flex items-end space-x-3 h-20 bg-gray-50 dark:bg-gray-800 rounded p-2">
                                        @php
                                            $maxValueMonth = max($stat['chart']) ?: 1;
                                        @endphp
                                        @foreach($stat['chart'] as $index => $value)
                                            <div class="flex-1 flex flex-col items-center">
                                                <div class="w-full flex justify-center mb-1">
                                                    <div
                                                        class="w-6 rounded-t-lg {{
                                                            $stat['color'] === 'success' ? 'bg-success-500' :
                                                            ($stat['color'] === 'warning' ? 'bg-warning-500' :
                                                            ($stat['color'] === 'danger' ? 'bg-danger-500' : 'bg-primary-500'))
                                                        }} hover:opacity-80 cursor-pointer transition-opacity border border-gray-300"
                                                        style="height: {{ $value > 0 ? max(6, ($value / $maxValueMonth) * 60) : 3 }}px"
                                                        title="Minggu {{ $index + 1 }}: Rp {{ number_format($value, 0, ',', '.') }}">
                                                    </div>
                                                </div>
                                                <div class="text-center">
                                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                                        Minggu {{ $index + 1 }}
                                                    </span>
                                                    @if($value > 0)
                                                        <div class="text-xs text-gray-500 dark:text-gray-500 mt-0.5">
                                                            @if($value >= 1000000)
                                                                {{ number_format($value / 1000000, 1) }} Juta
                                                            @elseif($value >= 1000)
                                                                {{ number_format($value / 1000, 0) }} Ribu
                                                            @else
                                                                {{ number_format($value, 0) }}
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </x-filament::card>
                    </div>
                @endif
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
