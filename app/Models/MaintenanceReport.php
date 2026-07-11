<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MaintenanceReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_task_id',
        'asset_id',
        'mechanic_id',
        'tanggal_pemeliharaan',
        'operating_hour_id',
        'catatan_pemeliharaan',
        'bukti_foto',
        'llm_suggestion',
    ];

    public function operatingHour()
    {
        return $this->belongsTo(OperatingHour::class);
    }

    public function maintenanceTask()
    {
        return $this->belongsTo(MaintenanceTask::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function mechanic()
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }
}