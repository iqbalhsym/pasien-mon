<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Admission extends Model
{
    protected $fillable = [
        'rm', 'name', 'insurance', 'room', 'floor', 'dpjp',
        'tgl_rencana_pulang', 'jam_rencana_pulang', 'tgl_aktual_pulang',
        'status_akhir', 'length_of_stay', 'total_bill', 'notes'
    ];
    protected $casts = [
        'total_bill' => 'float',
    ];
    public function checklist(): HasOne
    {
        return $this->hasOne(DischargeChecklist::class);
    }

    public function outstandingItems(): HasMany
    {
        return $this->hasMany(OutstandingItem::class);
    }
}
