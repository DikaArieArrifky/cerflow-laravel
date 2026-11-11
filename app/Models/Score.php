<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'map_id',
        'session_id',
        'score',
        'map_data',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'score'        => 'decimal:2',
            'map_data'     => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cerMap(): BelongsTo
    {
        return $this->belongsTo(CerMap::class, 'map_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(UserSession::class, 'session_id');
    }
}
