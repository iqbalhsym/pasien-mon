<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutstandingItem extends Model
{
    protected $fillable = [
        'admission_id', 'unit', 'category', 'item_name', 'price',
        'tgl_tindakan', 'tgl_input_sistem', 'status'
    ];

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }
}
