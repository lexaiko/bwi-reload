<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Downline extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'id_sales',
        'name',
        'kode_hari',
        'limit_saldo',
    ];

    protected $guarded = ['id'];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Get the sales (user) that owns the downline
     */
    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_sales');
    }
    public function alamats(): HasMany
{
    return $this->hasMany(Alamat::class, 'id_downline');
}


    /**
     * Get the transactions for the downline
     */
    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'id_downline');
    }

}

