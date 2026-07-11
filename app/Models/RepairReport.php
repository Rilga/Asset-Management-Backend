<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RepairReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_request_id',
        'asset_id',
        'mechanic_id',
        'operating_hour_id',
        'tanggal_perbaikan',
        'catatan_temuan',
        'cara_perbaikan',
        'bukti_foto',
        'llm_suggestion',
    ];

    public function repairRequest()
    {
        return $this->belongsTo(RepairRequest::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function mechanic()
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    public function operatingHour()
    {
        return $this->belongsTo(OperatingHour::class);
    }
}