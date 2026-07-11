<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RepairRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'mechanic_id',
        'kondisi_perbaikan',
        'catatan_kerusakan',
        'bukti_foto',
        'status_verifikasi',
        'verified_by',
        'verified_at',
        'catatan_verifikasi',
    ];

    public function repairReport()
    {
        return $this->hasOne(RepairReport::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function mechanic()
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}