<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserSession extends Model
{
    protected $table = 'user_sessions';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'map_id',
        'start_time',
        'is_submitted',
    ];

    protected function casts(): array
    {
        return [
            'start_time'   => 'datetime',
            'is_submitted' => 'boolean',
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

    public function logs(): HasMany
    {
        return $this->hasMany(UserLog::class, 'session_id');
    }

    public function score(): HasOne
    {
        return $this->hasOne(Score::class, 'session_id');
    }
}
