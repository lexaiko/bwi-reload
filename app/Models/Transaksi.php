<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaksi extends Model
{
    // add fillable
    protected $fillable = [
        'kode',
        'id_downline',
        'id_sales',
        'kode_hari',
        'minggu',
        'bulan',
        'tahun',
        'minus_pagi',
        'bayar',
        'sisa',
        'tanggal_transaksi',
    ];

    // add guaded
    protected $guarded = ['id'];

    // add hidden
    protected $hidden = ['created_at', 'updated_at'];

    // add casts
    protected $casts = [
        'minus_pagi' => 'float',
        'bayar' => 'float',
        'sisa' => 'float',
        'tanggal_transaksi' => 'datetime',
    ];

    /**
     * Get the sales (user) that owns the transaction
     */
    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_sales');
    }

    /**
     * Get the downline that owns the transaction
     */
    public function downline(): BelongsTo
    {
        return $this->belongsTo(Downline::class, 'id_downline');
    }
}
