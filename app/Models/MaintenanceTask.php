<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MaintenanceTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'assigned_by',
        'assigned_to',
        'tanggal_tugas',
        'catatan_tugas',
        'target_jam_jalan',
        'status',
    ];

    public function maintenanceReport()
    {
        return $this->hasOne(MaintenanceReport::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
