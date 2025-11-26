<?php

namespace App\Imports;

use App\Models\Downline;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Carbon\Carbon;

class TransaksiImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnError
{
    use SkipsErrors;

    protected $kodeHari;
    protected $minggu;
    protected $bulan;
    protected $tahun;
    protected $idSales;

    public function __construct($kodeHari, $minggu, $bulan, $tahun, $idSales)
    {
        $this->kodeHari = $kodeHari;
        $this->minggu = $minggu;
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->idSales = $idSales;
    }

    public function collection(Collection $rows)
    {
        Log::info("\n\n========================================");
        Log::info("IMPORT TRANSAKSI DIMULAI");
        Log::info("========================================");
        Log::info("Total rows dari Excel: " . $rows->count());

        // Filter out completely empty rows
        $rows = $rows->filter(function ($row) {
            return !empty(array_filter($row->toArray()));
        });

        Log::info("Rows setelah filter empty: " . $rows->count());

        // Step 1: Group main transaction data (NAMA, KODE, MINUS PAGI)
        $mainTransactions = [];

        // Step 2: Collect all transfers (TRANSFER SERVER, JUMLAH, TANGGAL)
        $transfersToProcess = [];

        foreach ($rows as $index => $row) {
            try {
                // Validate row has either main data or transfer data
                // Note: minus_pagi bisa 0, jadi pakai isset() bukan empty()
                $hasMainData = !empty($row['nama']) && !empty($row['kode']) && isset($row['minus_pagi']) && $row['minus_pagi'] !== null && $row['minus_pagi'] !== '';
                $hasTransferData = !empty($row['transfer_server']) && !empty($row['jumlah']);

                if (!$hasMainData && !$hasTransferData) {
                    Log::warning("Baris " . ($index + 2) . " diabaikan: tidak ada data utama atau transfer yang valid");
                    continue;
                }

                // Process main transaction data
                if ($hasMainData) {
                    $key = trim($row['kode']); // Gunakan hanya kode sebagai key (unik)
                    $namaFromExcel = trim($row['nama']);

                    Log::info("Baris " . ($index + 2) . " - Processing: Nama='{$namaFromExcel}', Kode='{$key}'");

                    // Jika kode sudah ada, update dengan nama terbaru dari Excel
                    if (!isset($mainTransactions[$key])) {
                        $mainTransactions[$key] = [
                            'nama' => $namaFromExcel,
                            'kode' => $key,
                            'minus_pagi' => $this->parseNumber($row['minus_pagi']),
                            'tanggal' => null, // Set null dulu, akan diupdate saat ada transfer
                        ];
                        Log::info("Kode '{$key}' BARU ditambahkan dengan nama: '{$namaFromExcel}'");
                    } else {
                        // Update nama jika ada data baru dengan kode sama
                        $namaLamaInArray = $mainTransactions[$key]['nama'];
                        $mainTransactions[$key]['nama'] = $namaFromExcel;
                        Log::info("Kode '{$key}' SUDAH ADA - Update nama dari '{$namaLamaInArray}' ke '{$namaFromExcel}'");
                    }
                }

                // Collect transfer data
                if ($hasTransferData) {
                    $transfersToProcess[] = [
                        'transfer_server' => $row['transfer_server'],
                        'jumlah' => $this->parseNumber($row['jumlah']),
                        'tanggal' => $this->parseDate($row['tanggal'])
                    ];
                }
            } catch (\Exception $e) {
                Log::error("Error processing row " . ($index + 2) . ": " . $e->getMessage());
                continue;
            }
        }

        Log::info("\n========================================");
        Log::info("STEP 3: Process Main Transactions");
        Log::info("Total main transactions: " . count($mainTransactions));
        Log::info("========================================\n");

        // Step 3: Process main transactions first
        foreach ($mainTransactions as $data) {
            $this->processMainTransaction($data);
        }

        Log::info("\n========================================");
        Log::info("STEP 4: Process Transfers");
        Log::info("Total transfers: " . count($transfersToProcess));
        Log::info("========================================\n");

        // Step 4: Process all transfers (group by target kode)
        $this->processAllTransfers($transfersToProcess);

        Log::info("\n========================================");
        Log::info("IMPORT TRANSAKSI SELESAI");
        Log::info("========================================\n\n");
    }

    protected function processMainTransaction($data)
    {
        Log::info("=== processMainTransaction START ===");
        Log::info("Data yang akan diproses - Nama: '{$data['nama']}', Kode: '{$data['kode']}'");

        // Find or create downline
        $downline = $this->findOrCreateDownline($data['nama'], $data['kode']);

        if (!$downline) {
            Log::warning("Downline tidak ditemukan atau dibuat untuk: {$data['nama']} - {$data['kode']}");
            return;
        }

        Log::info("Downline yang digunakan - ID: {$downline->id}, Nama: '{$downline->name}', Kode: '{$downline->kode}'");

        // Create or update main transaction (NAMA, KODE, MINUS PAGI)
        // minus_pagi dari Excel (negatif) disimpan sebagai negatif
        $minusPagi = $data['minus_pagi']; // Simpan apa adanya (negatif)

        // Set tanggal null jika belum ada data transfer
        $tanggalTransaksi = isset($data['tanggal']) && !empty($data['tanggal']) ? $data['tanggal'] : null;

        // Jika minus_pagi adalah 0, maka sisa tetap 0 (tidak ada kalkulasi)
        // Jika minus_pagi bukan 0 (negatif), maka sisa = minus_pagi
        $sisa = ($minusPagi == 0) ? 0 : $minusPagi;

        $transaksi = Transaksi::updateOrCreate(
            [
                'id_downline' => $downline->id,
                'minggu' => $this->minggu,
                'bulan' => $this->bulan,
                'tahun' => $this->tahun,
            ],
            [
                'id_sales' => $this->idSales,
                'kode_hari' => $this->kodeHari,
                'minus_pagi' => $minusPagi,
                'bayar' => 0, // Initialize bayar to 0, akan diupdate di step berikutnya
                'sisa' => $sisa, // 0 jika minus_pagi = 0, atau minus_pagi jika ada minus
                'tanggal_transaksi' => $tanggalTransaksi, // null jika belum ada transfer
            ]
        );

        Log::info("Transaksi utama berhasil diproses untuk downline: {$downline->name} (kode: {$downline->kode}), minus_pagi: {$minusPagi}, tanggal: " . ($tanggalTransaksi ?? 'null') . ", periode: minggu {$this->minggu}, bulan {$this->bulan}, tahun {$this->tahun}");
    }

    protected function processAllTransfers($transfers)
    {
        // Group transfers by target kode untuk menghitung total per downline
        $transfersByTarget = [];

        foreach ($transfers as $transfer) {
            // Extract target kode from transfer server
            if (preg_match('/Transfer ke ([A-Z0-9]+)/', $transfer['transfer_server'], $matches)) {
                $targetKode = $matches[1];

                if (!isset($transfersByTarget[$targetKode])) {
                    $transfersByTarget[$targetKode] = [
                        'total_bayar' => 0,
                        'tanggal_terakhir' => null,
                        'transfers' => []
                    ];
                }

                $jumlahTransfer = abs($transfer['jumlah']); // Convert to positive
                $transfersByTarget[$targetKode]['total_bayar'] += $jumlahTransfer;
                $transfersByTarget[$targetKode]['tanggal_terakhir'] = $transfer['tanggal'];
                $transfersByTarget[$targetKode]['transfers'][] = $transfer;
            }
        }

        // Update each target transaction
        foreach ($transfersByTarget as $targetKode => $transferData) {
            $this->updateTargetTransaction($targetKode, $transferData);
        }
    }

    protected function updateTargetTransaction($targetKode, $transferData)
    {
        // Find target downline
        $targetDownline = Downline::where('kode', $targetKode)->first();

        if ($targetDownline) {
            // Find target transaction for the same period
            $targetTransaction = Transaksi::where([
                'id_downline' => $targetDownline->id,
                'minggu' => $this->minggu,
                'bulan' => $this->bulan,
                'tahun' => $this->tahun,
            ])->first();

            if ($targetTransaction) {
                // Update bayar dengan total dari semua transfer ke downline ini
                $targetTransaction->bayar += $transferData['total_bayar'];

                // Calculate new sisa
                // Jika minus_pagi adalah 0, maka sisa tetap 0 (tidak ada kalkulasi)
                // Jika minus_pagi bukan 0 (negatif), maka sisa = minus_pagi + bayar
                if ($targetTransaction->minus_pagi == 0) {
                    $targetTransaction->sisa = 0;
                } else {
                    $targetTransaction->sisa = $targetTransaction->minus_pagi + $targetTransaction->bayar;
                }

                // Update tanggal dengan tanggal terakhir dari transfer
                if ($transferData['tanggal_terakhir']) {
                    $targetTransaction->tanggal_transaksi = $transferData['tanggal_terakhir'];
                } else if (is_null($targetTransaction->tanggal_transaksi)) {
                    $targetTransaction->tanggal_transaksi = now()->format('Y-m-d H:i:s');
                }

                $targetTransaction->save();

                Log::info("Transfer berhasil diproses ke downline {$targetDownline->name} (kode: {$targetKode}), minus_pagi: {$targetTransaction->minus_pagi}, bayar: {$targetTransaction->bayar}, sisa: {$targetTransaction->sisa} (minus_pagi + bayar)");
            } else {
                // Buat transaksi baru jika belum ada
                Log::warning("Transaksi target tidak ditemukan untuk downline kode: {$targetKode}, membuat transaksi baru");

                $newTransaction = Transaksi::create([
                    'id_downline' => $targetDownline->id,
                    'id_sales' => $this->idSales,
                    'kode_hari' => $this->kodeHari,
                    'minggu' => $this->minggu,
                    'bulan' => $this->bulan,
                    'tahun' => $this->tahun,
                    'minus_pagi' => 0, // Default 0 karena tidak ada data utama
                    'bayar' => $transferData['total_bayar'],
                    'sisa' => 0, // Jika minus_pagi = 0, maka sisa tetap 0
                    'tanggal_transaksi' => $transferData['tanggal_terakhir'] ?: now()->format('Y-m-d'),
                ]);

                Log::info("Transaksi baru dibuat untuk downline {$targetDownline->name} (kode: {$targetKode}), minus_pagi: 0, bayar: {$newTransaction->bayar}, sisa: 0");
            }
        } else {
            Log::warning("Downline target tidak ditemukan untuk kode: {$targetKode}");
        }
    }

    protected function findOrCreateDownline($nama, $kode)
    {
        // Clean up nama dan kode dari whitespace
        $nama = trim($nama);
        $kode = trim($kode);

        Log::info("\n=== findOrCreateDownline START ===");
        Log::info("Parameter - Nama: '{$nama}' (length: " . strlen($nama) . "), Kode: '{$kode}' (length: " . strlen($kode) . ")");

        // Find by exact kode match only (kode is unique identifier)
        $downline = Downline::where('kode', $kode)->first();

        if ($downline) {
            $namaLama = trim($downline->name);
            Log::info("Downline DITEMUKAN - ID: {$downline->id}");
            Log::info("Nama LAMA: '{$namaLama}' (length: " . strlen($namaLama) . ")");
            Log::info("Nama BARU: '{$nama}' (length: " . strlen($nama) . ")");
            Log::info("Sama? " . ($namaLama === $nama ? 'YA' : 'TIDAK'));

            // Update nama jika berbeda dari Excel (handle perubahan nama)
            // Bandingkan setelah di-trim untuk menghindari masalah whitespace
            if ($namaLama !== $nama) {
                Log::info("🔄 AKAN MENGUPDATE nama dari '{$namaLama}' ke '{$nama}' untuk kode: {$kode}");
                $downline->name = $nama;
                $saved = $downline->save();
                Log::info("Save result: " . ($saved ? 'TRUE' : 'FALSE'));

                // Verify update berhasil
                $downline->refresh();
                Log::info("✅ VERIFIKASI - Nama sekarang di database: '{$downline->name}'");
                Log::info("=== findOrCreateDownline END (UPDATED) ===\n");
            } else {
                Log::info("ℹ️ Nama SAMA, tidak perlu update");
                Log::info("=== findOrCreateDownline END (NO CHANGE) ===\n");
            }
            return $downline;
        }        // Create new downline if not found by kode
        $downline = Downline::create([
            'kode' => $kode,
            'name' => $nama,
            'kode_hari' => $this->kodeHari,
            'id_sales' => $this->idSales,
            'limit_saldo' => 0,
        ]);

        Log::info("✓ Downline BARU dibuat: {$nama} - {$kode}");

        return $downline;
    }

    protected function parseNumber($value)
    {
        // Remove currency symbols, spaces, and dots used as thousand separators
        $cleaned = preg_replace('/[^\d,.-]/', '', $value);

        // Handle negative numbers
        $isNegative = strpos($value, '-') !== false;

        // Remove minus sign for processing
        $cleaned = str_replace('-', '', $cleaned);

        // Convert comma to dot for decimal
        $cleaned = str_replace(',', '.', $cleaned);

        // Convert to float
        $number = floatval($cleaned);

        return $isNegative ? -$number : $number;
    }

    protected function parseDate($dateString)
    {
        try {
            // Debug log
            Log::info("Parsing date string: '{$dateString}'");

            // Clean up the date string
            $dateString = trim($dateString);

            if (empty($dateString)) {
                Log::warning("Empty date string, using current time");
                return now()->format('Y-m-d H:i:s');
            }

            // Handle Excel serial number format (e.g., 45899.386168981)
            if (is_numeric($dateString) && floatval($dateString) > 1) {
                $excelEpoch = Carbon::create(1900, 1, 1); // Excel epoch starts at 1900-01-01
                $days = floor(floatval($dateString)) - 2; // Excel counts from 1, and has a leap year bug
                $timeFloat = floatval($dateString) - floor(floatval($dateString));
                $seconds = $timeFloat * 24 * 60 * 60; // Convert decimal to seconds

                $parsedDate = $excelEpoch->addDays($days)->addSeconds($seconds);
                Log::info("Parsed Excel serial number {$dateString} to: " . $parsedDate->format('Y-m-d H:i:s'));
                return $parsedDate->format('Y-m-d H:i:s');
            }

            // Handle format like "30/08/2025  14:25:56" (with seconds)
            if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}):(\d{1,2}):(\d{1,2})/', $dateString, $matches)) {
                $parsedDate = Carbon::createFromFormat('d/m/Y H:i:s', $matches[1] . '/' . $matches[2] . '/' . $matches[3] . ' ' . $matches[4] . ':' . $matches[5] . ':' . $matches[6]);
                Log::info("Parsed datetime with seconds: " . $parsedDate->format('Y-m-d H:i:s'));
                return $parsedDate->format('Y-m-d H:i:s');
            }

            // Handle format like "30/08/2025 18:44" (without seconds)
            if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}):(\d{1,2})/', $dateString, $matches)) {
                $parsedDate = Carbon::createFromFormat('d/m/Y H:i', $matches[1] . '/' . $matches[2] . '/' . $matches[3] . ' ' . $matches[4] . ':' . $matches[5]);
                Log::info("Parsed datetime: " . $parsedDate->format('Y-m-d H:i:s'));
                return $parsedDate->format('Y-m-d H:i:s');
            }

            // Handle format like "30/08/2025" (without time)
            if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})/', $dateString, $matches)) {
                $parsedDate = Carbon::createFromFormat('d/m/Y', $matches[1] . '/' . $matches[2] . '/' . $matches[3]);
                Log::info("Parsed date only: " . $parsedDate->format('Y-m-d H:i:s'));
                return $parsedDate->format('Y-m-d H:i:s');
            }

            // Try Carbon's general parsing
            $parsedDate = Carbon::parse($dateString);
            Log::info("Carbon parsed: " . $parsedDate->format('Y-m-d H:i:s'));
            return $parsedDate->format('Y-m-d H:i:s');

        } catch (\Exception $e) {
            Log::error("Date parsing failed for '{$dateString}': " . $e->getMessage());
            return now()->format('Y-m-d H:i:s');
        }
    }

    public function rules(): array
    {
        return [
            // Tidak ada validasi strict karena kita handle validasi manual di collection()
            // Ini untuk menghindari error pada baris yang hanya berisi data transfer
        ];
    }

    public function customValidationMessages()
    {
        return [];
    }
}
