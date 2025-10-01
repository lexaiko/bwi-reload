<?php

namespace App\Filament\Resources\DownlineResource\Pages;

use App\Filament\Resources\DownlineResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDownline extends EditRecord
{
    protected static string $resource = DownlineResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
