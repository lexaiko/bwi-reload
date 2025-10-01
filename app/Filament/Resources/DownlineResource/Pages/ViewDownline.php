<?php

namespace App\Filament\Resources\DownlineResource\Pages;

use App\Filament\Resources\DownlineResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDownline extends ViewRecord
{
    protected static string $resource = DownlineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
