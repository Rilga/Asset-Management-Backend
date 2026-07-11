<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KnowledgeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'source_type',
        'source_id',
        'title',
        'content',
        'file_path',
        'status',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function chunks()
    {
        return $this->hasMany(KnowledgeChunk::class);
    }
}