<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'asset_id',
        'session_id',
        'question',
        'answer',
        'retrieved_context',
        'response_time_ms',
    ];

    public function retrievalLogs()
    {
        return $this->hasMany(RetrievalLog::class);
    }

    protected $casts = [
        'retrieved_context' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}