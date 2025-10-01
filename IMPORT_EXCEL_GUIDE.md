# Panduan Import Excel untuk Transaksi

## Cara Menggunakan Fit**Hasil perhitungan untuk F6 INDANA CELL (B40838):**
- `minus_pagi` = 433.715 (hutang awal)
- `bayar` = 450.000 + 230.000 + 150.000 + 100.000 = 930.000 (total semua transfer masuk)
- `sisa` = 433.715 + 930.000 = **1.363.715**

**Interpretasi nilai `sisa`:**
- `sisa` = total hutang yang bertambah dari minus_pagi + transfer masuk
- Semakin besar `sisa`, semakin besar total hutang downline tersebutmport Excel

### 1. Format Excel yang Diperlukan

File Excel harus memiliki header berikut:
```
NAMA | KODE | MINUS PAGI | TRANSFER SERVER | JUMLAH | TANGGAL
```

### 2. Penjelasan Kolom

- **NAMA**: Nama downline (contoh: F6 ADAM CELL (AR) T)
- **KODE**: Kode downline (contoh: B43475)
- **MINUS PAGI**: Nominal minus pagi (contoh: -189078)
- **TRANSFER SERVER**: Deskripsi transfer dengan format "Transfer ke [KODE_TARGET] - [NAMA_TARGET]"
- **JUMLAH**: Nominal transfer (contoh: -450000)
- **TANGGAL**: Tanggal transaksi format dd/mm/yyyy hh:mm (contoh: 30/08/2025 18:44)

### 3. Proses Import

Fitur import ini bekerja dalam 4 tahap:

#### Tahap 1: Kumpulkan Data Utama (NAMA, KODE, MINUS PAGI)
- Sistem mengumpulkan semua data utama transaksi dari Excel
- Data dikelompokkan berdasarkan NAMA dan KODE

#### Tahap 2: Kumpulkan Data Transfer (TRANSFER SERVER, JUMLAH, TANGGAL)
- Sistem mengumpulkan semua data transfer dari Excel
- Data transfer dikelompokkan berdasarkan kode target yang diekstrak dari TRANSFER SERVER

#### Tahap 3: Proses Data Utama
- Sistem mencari downline berdasarkan KODE
- Jika downline tidak ditemukan, akan dibuat downline baru
- Membuat/update transaksi dengan:
  - `minus_pagi` = nilai dari kolom MINUS PAGI
  - `bayar` = 0 (initial, akan diupdate di tahap berikutnya)
  - `sisa` = nilai minus_pagi (initial)

#### Tahap 4: Proses Semua Transfer
- Sistem mengelompokkan transfer berdasarkan kode target
- **Beberapa baris transfer dapat menuju ke satu downline yang sama**
- Total semua transfer ke downline yang sama dijumlahkan
- Cari transaksi target berdasarkan kode dan periode yang sama
- **Jika transaksi target tidak ditemukan, sistem akan membuat transaksi baru**
- Update/buat transaksi target:
  - `bayar` += total jumlah transfer ke downline ini
  - `sisa` = `minus_pagi` + `bayar`
  - `tanggal_transaksi` = tanggal terakhir dari transfer

### 4. Contoh Data

```excel
NAMA                    | KODE   | MINUS PAGI | TRANSFER SERVER                      | JUMLAH  | TANGGAL
F6 ADAM CELL (AR) T     | B43475 | -189078    | Transfer ke B40838 - F6 INDANA CELL  | -450000 | 30/08/2025 18:44
F6 AGUNG CELL VJ        | B42413 | -344700    | Transfer ke B40838 - F6 INDANA CELL  | -230000 | 30/08/2025 14:25
F6 INDANA CELL          | B40838 | -433715    | Transfer ke B41045 - F6 ZAHRA CELL   | -200000 | 30/08/2025 11:39
                        |        |            | Transfer ke B40838 - F6 INDANA CELL  | -150000 | 30/08/2025 10:15
```

**Penjelasan contoh di atas:**
- Baris 1: F6 ADAM CELL transfer 450.000 ke F6 INDANA CELL (B40838)
- Baris 2: F6 AGUNG CELL transfer 230.000 ke F6 INDANA CELL (B40838) 
- Baris 3: F6 INDANA CELL punya minus pagi 433.715
- Baris 4: Ada transfer tambahan 150.000 ke F6 INDANA CELL (B40838)

**Hasil perhitungan untuk F6 INDANA CELL (B40838):**
- `minus_pagi` = -433.715 (negatif, disimpan apa adanya dari Excel)
- `bayar` = 450.000 + 230.000 + 150.000 + 100.000 = 930.000 (total semua transfer masuk, positif)
- `sisa` = (-433.715) + 930.000 = **496.285** (positif)

### 5. Cara Import di Aplikasi

1. Buka halaman **Transaksi**
2. Klik tombol **Import Excel** di bagian atas tabel
3. Isi form filter:
   - **Kode Hari**: Pilih hari (1-6)
   - **Minggu**: Masukkan minggu ke berapa
   - **Bulan**: Pilih bulan
   - **Tahun**: Masukkan tahun
   - **Sales**: Pilih sales yang bertanggung jawab
4. Upload file Excel
5. Klik **Import**

### 6. Download Template

Klik tombol **Download Template Excel** untuk mendapatkan file template dengan format yang benar.

### 7. Catatan Penting

- **Baris fleksibel**: Baris bisa berisi hanya data utama (NAMA, KODE, MINUS PAGI) atau hanya data transfer (TRANSFER SERVER, JUMLAH)
- **Baris kosong**: Baris yang sepenuhnya kosong akan diabaikan
- Semua transaksi akan difilter berdasarkan periode yang dipilih (kode_hari, minggu, bulan, tahun)
- Jika downline dengan KODE tidak ditemukan, sistem akan membuat downline baru
- Transfer hanya akan diproses jika ada transaksi target dengan kode yang sesuai pada periode yang sama
- Format angka bisa menggunakan tanda titik sebagai pemisah ribuan, sistem akan otomatis membersihkannya
- Nilai JUMLAH sebaiknya negatif untuk menunjukkan transfer keluar

### 8. Troubleshooting

**Q: Import gagal dengan error "Kolom NAMA harus diisi"**
A: Pastikan setiap baris memiliki setidaknya data utama (NAMA, KODE, MINUS PAGI) atau data transfer (TRANSFER SERVER, JUMLAH). Baris yang sepenuhnya kosong akan diabaikan.

**Q: Import gagal dengan error "Downline tidak ditemukan"**
A: Pastikan KODE downline sudah benar dan sesuai format

**Q: Transfer tidak masuk ke target**
A: Pastikan format TRANSFER SERVER benar: "Transfer ke [KODE] - [NAMA]" dan kode target sudah ada di data

**Q: Sisa tidak terhitung dengan benar**
A: Rumus sisa = minus_pagi + bayar. 
- `sisa` = total hutang yang bertambah
- `minus_pagi` = hutang awal
- `bayar` = transfer masuk yang menambah hutang
Pastikan nilai minus_pagi dan jumlah transfer benar

**Q: Kolom "Bayar" kosong/nol di tabel transaksi**
A: Ini normal untuk transaksi yang tidak menerima transfer. Kolom "Bayar" hanya terisi untuk downline yang menerima transfer dari downline lain. Cek log untuk memastikan transfer diproses dengan benar.

**Q: Beberapa baris diabaikan saat import**
A: Sistem akan mengabaikan baris yang tidak memiliki data utama atau data transfer yang valid. Cek log untuk detail baris mana yang diabaikan.
