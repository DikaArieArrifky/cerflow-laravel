<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLog extends Model
{
    protected $table = 'user_logs';

    /**
     * Only created_at exists, no updated_at.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'session_id',
        'action_type',
        'action_data',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'action_data' => 'array',
            'created_at'  => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    public function session(): BelongsTo
    {
        return $this->belongsTo(UserSession::class, 'session_id');
    }
}
