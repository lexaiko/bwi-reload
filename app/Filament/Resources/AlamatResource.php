<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Alamat;
use App\Models\Downline;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\AlamatResource\Pages;

class AlamatResource extends Resource
{
    protected static ?string $model = Alamat::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Alamat';

    protected static ?string $pluralModelLabel = 'Alamat';

    protected static ?int $navigationSort = -4;

    protected static ?string $navigationGroup = 'Downline Management';


    public static function isSales(): bool
    {
        return auth()->user()->hasRole('sales');
    }
    /**
     * Apply sales-specific query modifications
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Jika user adalah sales, filter hanya alamat dari downline mereka
        if (static::isSales()) {
            $query->whereHas('downline', function ($q) {
                $q->where('id_sales', auth()->id());
            });
        }

        return $query;
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Alamat')
                    ->schema([
                        Forms\Components\Select::make('id_downline')
                            ->label('Downline')
                            ->relationship(
                                'downline',
                                'name',
                                fn ($query) => static::isSales()
                                    ? $query->where('id_sales', auth()->id())
                                    : $query
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('maps')
                            ->label('Link Maps/Koordinat')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan link Google Maps atau koordinat')
                            ->url()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('deskripsi')
                            ->label('Deskripsi Alamat')
                            ->placeholder('Deskripsi detail alamat (opsional)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('downline.kode')
                    ->label('Downline')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('downline.name')
                    ->label('Nama Downline')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('downline.sales.name')
                    ->label('Sales')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),



                TextColumn::make('maps')
                    ->label('Maps/Koordinat')
                    ->url(fn ($record) => $record->maps) // ini baru jalan kalau tanpa copyable
                    ->openUrlInNewTab()
                    ->limit(50),


                Tables\Columns\TextColumn::make('deskripsi')
                    ->label('Deskripsi')
                    ->searchable()
                    ->limit(40)
                    ->placeholder('Tidak ada deskripsi')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('id_downline')
                    ->label('Downline')
                    ->options(function () {
                        if (auth()->user()->hasRole('sales')) {
                            return \App\Models\Downline::where('id_sales', auth()->id())
                                ->pluck('name', 'id');
                        }
                        return \App\Models\Downline::pluck('name', 'id');
                    })
                    ->searchable(),
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
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
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
            'index' => Pages\ListAlamats::route('/'),
            'create' => Pages\CreateAlamat::route('/create'),
            'view' => Pages\ViewAlamat::route('/{record}'),
            'edit' => Pages\EditAlamat::route('/{record}/edit'),
        ];
    }
}
