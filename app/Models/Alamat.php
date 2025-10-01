<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alamat extends Model
{
    // add fillable
    protected $fillable = [
        'id_downline',
        'maps',
        'deskripsi'
    ];

    // add guaded
    protected $guarded = ['id'];

    // add hidden
    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Get the downline that owns the alamat
     */
    public function downline(): BelongsTo
    {
        return $this->belongsTo(Downline::class, 'id_downline');
    }
}





