<?php

namespace App\Filament\Resources\DownlineResource\Pages;

use App\Filament\Resources\DownlineResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDownline extends CreateRecord
{
    protected static string $resource = DownlineResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
