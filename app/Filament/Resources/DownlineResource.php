<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DownlineResource\Pages;
use App\Filament\Resources\DownlineResource\RelationManagers;
use App\Models\Downline;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DownlineResource extends Resource
{
    protected static ?string $model = Downline::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Downlines';

    protected static ?string $modelLabel = 'Downline';

    protected static ?string $pluralModelLabel = 'Downlines';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('kode')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->label('Kode'),

                Forms\Components\Select::make('id_sales')
                    ->relationship('sales', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Sales'),

                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama'),


                Forms\Components\Select::make('kode_hari')
                    ->required()
                    ->options([
                        '1' => 'Senin',
                        '2' => 'Selasa',
                        '3' => 'Rabu',
                        '4' => 'Kamis',
                        '5' => 'Jumat',
                        '6' => 'Sabtu',
                    ])
                    ->label('Kode Hari'),

                Forms\Components\TextInput::make('limit_saldo')
                    ->required()
                    ->numeric()
                    ->label('Limit Saldo')
                    ->prefix('Rp')
                    ->placeholder('0'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('kode')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge(),

                Tables\Columns\TextColumn::make('sales.name')
                    ->label('Sales')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

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

                Tables\Columns\TextColumn::make('limit_saldo')
                    ->label('Limit Saldo')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diupdate')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('id_sales')
                    ->relationship('sales', 'name')
                    ->label('Sales')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('kode_hari')
                    ->label('Kode Hari')
                    ->options([
                        '1' => 'Senin',
                        '2' => 'Selasa',
                        '3' => 'Rabu',
                        '4' => 'Kamis',
                        '5' => 'Jumat',
                        '6' => 'Sabtu',
                    ]),
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
            'index' => Pages\ListDownlines::route('/'),
            'create' => Pages\CreateDownline::route('/create'),
            'view' => Pages\ViewDownline::route('/{record}'),
            'edit' => Pages\EditDownline::route('/{record}/edit'),
        ];
    }
}
