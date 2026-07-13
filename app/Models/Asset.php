<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Asset extends Model
{
    use HasFactory;

    protected $appends = [
        'qr_code_url',
        'qr_detail_url',
        'foto_kondisi_url',
        'manual_book_url',
    ];

    protected $fillable = [
        'kategori',
        'nomor_peralatan',
        'nama_mesin',
        'area_mesin',
        'merek',
        'tahun_pembelian',
        'maintenance_interval_hours',
        'foto_kondisi',
        'manual_book',
        'qr_code_path',
        'created_by',
    ];

    public function chatHistories()
    {
        return $this->hasMany(ChatHistory::class);
    }

    public function knowledgeChunks()
    {
        return $this->hasMany(KnowledgeChunk::class);
    }

    public function knowledgeDocuments()
    {
        return $this->hasMany(KnowledgeDocument::class);
    }

    public function getQrCodeUrlAttribute()
    {
        if (!$this->qr_code_path) return null;

        $baseUrl = rtrim(config('services.supabase.url'), '/');
        $bucket = config('services.supabase.bucket');

        return "{$baseUrl}/storage/v1/object/public/{$bucket}/{$this->qr_code_path}";
    }

    public function getFotoKondisiUrlAttribute(): ?string
    {
        if (!$this->foto_kondisi) return null;

        $baseUrl = rtrim(config('services.supabase.url'), '/');
        $bucket = config('services.supabase.bucket');

        return "{$baseUrl}/storage/v1/object/public/{$bucket}/{$this->foto_kondisi}";
    }

    public function getManualBookUrlAttribute(): ?string
    {
        if (!$this->manual_book) return null;
        $baseUrl = rtrim(config('services.supabase.url'), '/');
        $bucket = config('services.supabase.bucket');
        return "{$baseUrl}/storage/v1/object/public/{$bucket}/{$this->manual_book}";
    }

    public function getQrDetailUrlAttribute()
    {
        return route('assets.qr-detail', $this);
    }

    public function repairReports()
    {
        return $this->hasMany(RepairReport::class);
    }

    public function repairRequests()
    {
        return $this->hasMany(RepairRequest::class);
    }

    public function maintenanceReport()
    {
        return $this->hasMany(MaintenanceReport::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function operatingHours()
    {
        return $this->hasMany(OperatingHour::class);
    }

    public function maintenanceTasks()
    {
        return $this->hasMany(MaintenanceTask::class);
    }

    public function maintenanceReports()
    {
        return $this->hasMany(MaintenanceReport::class);
    }
}
