<?php

namespace App\Filament\Resources\TransaksiResource\Pages;

use App\Filament\Resources\TransaksiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTransaksi extends ViewRecord
{
    protected static string $resource = TransaksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Debug: Log data yang akan ditampilkan
        \Log::info('ViewTransaksi data:', $data);

        // Convert string numbers to proper format
        $data['minus_pagi'] = (float) ($data['minus_pagi'] ?? 0);
        $data['bayar'] = (float) ($data['bayar'] ?? 0);
        $data['sisa'] = (float) ($data['sisa'] ?? 0);

        \Log::info('ViewTransaksi data after conversion:', $data);

        return $data;
    }
}
