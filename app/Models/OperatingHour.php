<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OperatingHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'user_id',
        'tanggal',
        'jam_jalan',
        'keterangan',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
