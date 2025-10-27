<?php

namespace App\Filament\Widgets;

use App\Models\Transaksi;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;

class TransaksiPerMingguWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.transaksi-per-minggu-widget';

    // Properties untuk filter
    public ?string $bulan = null;
    public ?string $tahun = null;
    public ?string $sales = null;

    public function mount(): void
    {
        // Set default filter ke bulan dan tahun saat ini
        $this->bulan = (string) date('n');
        $this->tahun = (string) date('Y');
        $this->sales = 'all';

        // Initialize form dengan data default
        $this->form->fill([
            'bulan' => $this->bulan,
            'tahun' => $this->tahun,
            'sales' => $this->sales,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('bulan')
                    ->label('Bulan')
                    ->options([
                        '1' => 'Januari',
                        '2' => 'Februari',
                        '3' => 'Maret',
                        '4' => 'April',
                        '5' => 'Mei',
                        '6' => 'Juni',
                        '7' => 'Juli',
                        '8' => 'Agustus',
                        '9' => 'September',
                        '10' => 'Oktober',
                        '11' => 'November',
                        '12' => 'Desember',
                    ])
                    ->selectablePlaceholder(false)
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->bulan = $state;
                    }),

                Forms\Components\Select::make('tahun')
                    ->label('Tahun')
                    ->options(
                        collect(range(2020, 2030))
                            ->mapWithKeys(fn($year) => [(string)$year => (string)$year])
                            ->toArray()
                    )
                    ->selectablePlaceholder(false)
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->tahun = $state;
                    }),

                Forms\Components\Select::make('sales')
                    ->label('Sales')
                    ->options(function () {
                        $options = ['all' => 'Semua Sales'];

                        $salesUsers = User::role('sales')
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(function ($user) {
                                return [$user->id => $user->name . ($user->kode_sales ? " ({$user->kode_sales})" : '')];
                            })
                            ->toArray();

                        return $options + $salesUsers;
                    })
                    ->default('all')
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->sales = $state;
                    }),
            ])
            ->columns(3);
    }

    public function getStats(): array
    {
        $bulan = (int) ($this->bulan ?? date('n'));
        $tahun = (int) ($this->tahun ?? date('Y'));

        $stats = [];

        // Card Total Bulan (card pertama)
        $totalBulanQuery = Transaksi::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun);

        // Apply sales filter
        if ($this->sales && $this->sales !== 'all') {
            $totalBulanQuery->where('id_sales', $this->sales);
        } elseif (auth()->user()->hasRole('sales')) {
            $totalBulanQuery->where('id_sales', auth()->id());
        }

        $totalBulanData = $totalBulanQuery->get();
        $totalTransaksiBulan = $totalBulanData->count();
        $totalMinusPagiBulan = $totalBulanData->sum('minus_pagi');
        $totalBayarBulan = $totalBulanData->sum('bayar');
        $totalSisaBulan = $totalBulanData->sum('sisa');

        // Tentukan warna untuk total bulan
        $colorBulan = 'primary';
        if ($totalSisaBulan > 0) {
            $colorBulan = 'warning';
        } elseif ($totalSisaBulan < 0) {
            $colorBulan = 'danger';
        } else {
            $colorBulan = 'success';
        }

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ][$bulan] ?? '';

        $stats[] = [
            'title' => "Total {$namaBulan} {$tahun}",
            'value' => "{$totalTransaksiBulan} Transaksi",
            'description' => [
                'bayar' => number_format($totalBayarBulan, 0, ',', '.'),
                'minus' => number_format($totalMinusPagiBulan, 0, ',', '.'),
                'sisa' => number_format($totalSisaBulan, 0, ',', '.'),
            ],
            'color' => $colorBulan,
            'chart' => $this->getMonthlyChartData($bulan, $tahun),
            'is_total' => true,
        ];

        // Loop untuk setiap minggu (card 2-5)
        for ($minggu = 1; $minggu <= 4; $minggu++) {
            // Query base tanpa raw SQL
            $query = Transaksi::query()
                ->where('minggu', $minggu)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun);

            // Apply sales filter
            if ($this->sales && $this->sales !== 'all') {
                $query->where('id_sales', $this->sales);
            } elseif (auth()->user()->hasRole('sales')) {
                $query->where('id_sales', auth()->id());
            }

            // Ambil data dan hitung aggregasi menggunakan collection
            $data = $query->get();
            $totalTransaksi = $data->count();
            $totalMinusPagi = $data->sum('minus_pagi');
            $totalBayar = $data->sum('bayar');
            $totalSisa = $data->sum('sisa');

            // Tentukan warna berdasarkan sisa
            $color = 'success';
            if ($totalSisa > 0) {
                $color = 'warning';
            } elseif ($totalSisa < 0) {
                $color = 'danger';
            }

            $stats[] = [
                'title' => "Minggu {$minggu}",
                'value' => "{$totalTransaksi} Transaksi",
                'description' => [
                    'bayar' => number_format($totalBayar, 0, ',', '.'),
                    'minus' => number_format($totalMinusPagi, 0, ',', '.'),
                    'sisa' => number_format($totalSisa, 0, ',', '.'),
                ],
                'color' => $color,
                'chart' => $this->getChartData($minggu, $bulan, $tahun),
                'is_total' => false,
            ];
        }

        return $stats;
    }

    protected function getMonthlyChartData(int $bulan, int $tahun): array
    {
        // Chart data untuk total bulan (per minggu)
        $chartData = [];

        for ($minggu = 1; $minggu <= 4; $minggu++) {
            $query = Transaksi::query()
                ->where('minggu', $minggu)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun);

            // Apply sales filter
            if ($this->sales && $this->sales !== 'all') {
                $query->where('id_sales', $this->sales);
            } elseif (auth()->user()->hasRole('sales')) {
                $query->where('id_sales', auth()->id());
            }

            $chartData[] = $query->sum('bayar');
        }

        return $chartData;
    }

    protected function getChartData(int $minggu, int $bulan, int $tahun): array
    {
        // Query untuk mendapatkan data transaksi per hari dalam minggu
        $query = Transaksi::query()
            ->where('minggu', $minggu)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun);

        // Apply sales filter
        if ($this->sales && $this->sales !== 'all') {
            $query->where('id_sales', $this->sales);
        } elseif (auth()->user()->hasRole('sales')) {
            $query->where('id_sales', auth()->id());
        }

        // Group by kode_hari dan hitung total bayar tanpa raw SQL
        $data = $query->get()
            ->groupBy('kode_hari')
            ->map(function ($group) {
                return $group->sum('bayar');
            })
            ->toArray();

        // Format data untuk chart (6 hari: Senin-Sabtu, kode 1-6)
        $chartData = [];
        for ($i = 1; $i <= 6; $i++) {
            $chartData[] = $data[$i] ?? 0;
        }

        return $chartData;
    }

    public static function canView(): bool
    {
        return true;
    }
}
