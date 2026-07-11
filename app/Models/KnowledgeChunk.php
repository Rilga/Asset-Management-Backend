<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KnowledgeChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'knowledge_document_id',
        'asset_id',
        'chunk_text',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function knowledgeDocument()
    {
        return $this->belongsTo(KnowledgeDocument::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}