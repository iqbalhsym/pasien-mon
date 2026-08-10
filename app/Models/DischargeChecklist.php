<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DischargeChecklist extends Model
{
    protected $fillable = [
        'admission_id', 'lab_status', 'lab_detail', 'bedside_status',
        'admin_ok_status', 'farmasi_status', 'radiologi_status', 'cot_status',
        'operator_fee', 'anestesi_fee', 'keterangan_proses'
    ];

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }
}
