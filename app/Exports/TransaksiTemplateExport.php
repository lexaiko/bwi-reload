<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class TransaksiTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function array(): array
    {
        return [
            // Contoh 1: Data utama dengan transfer
            [
                'F6 ADAM CELL (AR) T',
                'B43475',
                '-189078',
                'Transfer ke B40838 - F6 INDANA CELL',
                '-450000',
                '30/08/2025 18:44'
            ],
            // Contoh 2: Data utama dengan transfer ke downline yang sama (B40838)
            [
                'F6 AGUNG CELL VJ',
                'B42413',
                '-344700',
                'Transfer ke B40838 - F6 INDANA CELL',
                '-230000',
                '30/08/2025 14:25'
            ],
            // Contoh 3: Data utama target yang menerima transfer
            [
                'F6 INDANA CELL',
                'B40838',
                '-433715',
                'Transfer ke B41045 - F6 ZAHRA CELL (B)',
                '-200000',
                '30/08/2025 11:39'
            ],
            // Contoh 4: Transfer tambahan ke downline yang sama (B40838) tanpa data utama
            [
                null,
                null,
                null,
                'Transfer ke B40838 - F6 INDANA CELL',
                '-150000',
                '30/08/2025 10:15'
            ],
            // Contoh 5: Transfer lain ke downline yang sama (B40838)
            [
                null,
                null,
                null,
                'Transfer ke B40838 - F6 INDANA CELL',
                '-100000',
                '30/08/2025 09:30'
            ],
            // Baris kosong sebagai template
            ['', '', '', '', '', ''],
            ['', '', '', '', '', ''],
        ];
    }

    public function headings(): array
    {
        return [
            'NAMA',
            'KODE',
            'MINUS PAGI',
            'TRANSFER SERVER',
            'JUMLAH',
            'TANGGAL'
        ];
    }
}
