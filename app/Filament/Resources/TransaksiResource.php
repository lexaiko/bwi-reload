<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransaksiResource\Pages;
use App\Models\Transaksi;
use App\Models\Downline;
use App\Models\User;
use App\Imports\TransaksiImport;
use App\Exports\TransaksiTemplateExport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class TransaksiResource extends Resource
{
    protected static ?string $model = Transaksi::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Transaksi';

    protected static ?string $pluralModelLabel = 'Transaksi';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Transaksi')
                    ->schema([
                        Forms\Components\Select::make('id_downline')
                            ->label('Downline')
                            ->relationship('downline', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    // Get the selected downline and auto-fill sales & kode_hari
                                    $downline = \App\Models\Downline::find($state);
                                    if ($downline) {
                                        if ($downline->id_sales) {
                                            $set('id_sales', $downline->id_sales);
                                        }
                                        if ($downline->kode_hari) {
                                            $set('kode_hari', $downline->kode_hari);
                                        }
                                    }
                                }
                            }),

                        Forms\Components\Select::make('id_sales')
                            ->label('Sales')
                            ->relationship('sales', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Akan terisi otomatis berdasarkan downline yang dipilih'),

                        Forms\Components\Select::make('kode_hari')
                            ->label('Kode Hari')
                            ->required()
                            ->options([
                                '1' => 'Senin',
                                '2' => 'Selasa',
                                '3' => 'Rabu',
                                '4' => 'Kamis',
                                '5' => 'Jumat',
                                '6' => 'Sabtu',
                            ])
                            ->helperText('Akan terisi otomatis berdasarkan downline yang dipilih'),
                    ])->columns(3),

                Forms\Components\Section::make('Periode')
                    ->schema([
                        Forms\Components\TextInput::make('minggu')
                            ->label('Minggu')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(4),

                        Forms\Components\Select::make('bulan')
                            ->label('Bulan')
                            ->required()
                            ->options([
                                1 => 'Januari',
                                2 => 'Februari',
                                3 => 'Maret',
                                4 => 'April',
                                5 => 'Mei',
                                6 => 'Juni',
                                7 => 'Juli',
                                8 => 'Agustus',
                                9 => 'September',
                                10 => 'Oktober',
                                11 => 'November',
                                12 => 'Desember',
                            ])
                            ->default(date('n')),

                        Forms\Components\TextInput::make('tahun')
                            ->label('Tahun')
                            ->required()
                            ->numeric()
                            ->minValue(2020)
                            ->maxValue(2030)
                            ->default(date('Y')),
                    ])->columns(3),

                Forms\Components\Section::make('Detail Keuangan')
                    ->schema([
                        Forms\Components\TextInput::make('minus_pagi')
                            ->label('Minus Pagi')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->disabled(fn ($context) => $context === 'view'),

                        Forms\Components\TextInput::make('bayar')
                            ->label('Bayar')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->disabled(fn ($context) => $context === 'view'),

                        Forms\Components\TextInput::make('sisa')
                            ->label('Sisa')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->disabled(fn ($context) => $context === 'view'),

                        Forms\Components\DatePicker::make('tanggal_transaksi')
                            ->label('Tanggal Transaksi')
                            ->default(now()),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('downline.name')
                    ->label('Downline')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sales.name')
                    ->label('Sales')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('kode_hari')
                    ->label('Kode Hari')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '1' => 'Senin',
                        '2' => 'Selasa',
                        '3' => 'Rabu',
                        '4' => 'Kamis',
                        '5' => 'Jumat',
                        '6' => 'Sabtu',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('minus_pagi')
                    ->label('Minus Pagi')
                    ->money('IDR')
                    ->sortable()
                    ->color('danger')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('bayar')
                    ->label('Bayar')
                    ->money('IDR')
                    ->sortable()
                    ->color('success')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sisa')
                    ->label('Sisa')
                    ->money('IDR')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'success')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('minggu')
                    ->label('Minggu')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('bulan')
                    ->label('Bulan')
                    ->formatStateUsing(fn ($state) => match($state) {
                        1 => 'Januari',
                        2 => 'Februari',
                        3 => 'Maret',
                        4 => 'April',
                        5 => 'Mei',
                        6 => 'Juni',
                        7 => 'Juli',
                        8 => 'Agustus',
                        9 => 'September',
                        10 => 'Oktober',
                        11 => 'November',
                        12 => 'Desember',
                        default => $state
                    })
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('id_sales')
                    ->label('Sales')
                    ->relationship('sales', 'name')
                    ->searchable()
                    ->preload(),

                // SelectFilter::make('id_downline')
                //     ->label('Downline')
                //     ->relationship('downline', 'name')
                //     ->searchable()
                //     ->preload(),

                SelectFilter::make('kode_hari')
                    ->label('Kode Hari')
                    ->options(function () {
                        return \App\Models\Downline::query()
                            ->select('kode_hari')
                            ->distinct()
                            ->orderBy('kode_hari')
                            ->pluck('kode_hari', 'kode_hari')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $value): Builder => $query->whereHas(
                                'downline',
                                fn (Builder $query): Builder => $query->where('kode_hari', $value)
                            ),
                        );
                    }),

                SelectFilter::make('minggu')
                    ->label('Minggu')
                    ->options([
                        1 => '1',
                        2 => '2',
                        3 => '3',
                        4 => '4',
                    ])
                    ->searchable()
                    ->preload(),

                SelectFilter::make('bulan')
                    ->label('Bulan')
                    ->options([
                        1 => 'Januari',
                        2 => 'Februari',
                        3 => 'Maret',
                        4 => 'April',
                        5 => 'Mei',
                        6 => 'Juni',
                        7 => 'Juli',
                        8 => 'Agustus',
                        9 => 'September',
                        10 => 'Oktober',
                        11 => 'November',
                        12 => 'Desember',
                    ]),

                SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options(
                        collect(range(2020, 2030))->mapWithKeys(fn($year) => [$year => $year])
                    ),

                Filter::make('sisa_lebih_dari_nol')
                    ->label('Masih Ada Sisa')
                    ->query(fn (Builder $query): Builder => $query->where('sisa', '>', 0)),
            ])
            ->headerActions([
                Action::make('download_template')
                    ->label('Download Template')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(function () {
                        return Excel::download(new TransaksiTemplateExport, 'template_transaksi.xlsx');
                    }),

                Action::make('import_excel')
                    ->label('Import Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        Forms\Components\Section::make('Filter Import')
                            ->description('Tentukan kode hari, minggu, bulan, dan tahun untuk data yang akan diimport')
                            ->schema([
                                Forms\Components\Select::make('kode_hari')
                                    ->label('Kode Hari')
                                    ->required()
                                    ->options([
                                        '1' => 'Senin',
                                        '2' => 'Selasa',
                                        '3' => 'Rabu',
                                        '4' => 'Kamis',
                                        '5' => 'Jumat',
                                        '6' => 'Sabtu',
                                    ]),

                                Forms\Components\TextInput::make('minggu')
                                    ->label('Minggu Ke')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(4)
                                    ->default(1),

                                Forms\Components\Select::make('bulan')
                                    ->label('Bulan')
                                    ->required()
                                    ->options([
                                        1 => 'Januari',
                                        2 => 'Februari',
                                        3 => 'Maret',
                                        4 => 'April',
                                        5 => 'Mei',
                                        6 => 'Juni',
                                        7 => 'Juli',
                                        8 => 'Agustus',
                                        9 => 'September',
                                        10 => 'Oktober',
                                        11 => 'November',
                                        12 => 'Desember',
                                    ])
                                    ->default(date('n')),

                                Forms\Components\TextInput::make('tahun')
                                    ->label('Tahun')
                                    ->required()
                                    ->numeric()
                                    ->minValue(2020)
                                    ->maxValue(2030)
                                    ->default(date('Y')),

                                Forms\Components\Select::make('id_sales')
                                    ->label('Sales')
                                    ->options(User::all()->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                            ])->columns(3),

                        Forms\Components\Section::make('File Excel')
                            ->schema([
                                Forms\Components\FileUpload::make('excel_file')
                                    ->label('Upload File Excel')
                                    ->required()
                                    ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                                    ->maxSize(5120)
                                    ->helperText('Format: NAMA | KODE | MINUS PAGI | TRANSFER SERVER | JUMLAH | TANGGAL'),
                            ]),
                    ])
                    ->action(function (array $data) {
                        try {
                            $filePath = storage_path('app/public/' . $data['excel_file']);

                            $import = new TransaksiImport(
                                $data['kode_hari'],
                                $data['minggu'],
                                $data['bulan'],
                                $data['tahun'],
                                $data['id_sales']
                            );

                            Excel::import($import, $filePath);

                            // Clean up uploaded file
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }

                            Notification::make()
                                ->title('Import Berhasil')
                                ->body('Data transaksi berhasil diimport dari file Excel.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Import Gagal')
                                ->body('Terjadi kesalahan: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalWidth('4xl'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransaksis::route('/'),
            'create' => Pages\CreateTransaksi::route('/create'),
            'view' => Pages\ViewTransaksi::route('/{record}'),
            'edit' => Pages\EditTransaksi::route('/{record}/edit'),
        ];
    }
}
