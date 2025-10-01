<?php

namespace App\Filament\Resources\AlamatResource\Pages;

use App\Filament\Resources\AlamatResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAlamat extends ViewRecord
{
    protected static string $resource = AlamatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
