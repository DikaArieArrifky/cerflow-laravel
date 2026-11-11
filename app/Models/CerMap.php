<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CerMap extends Model
{
    protected $table = 'cer_maps';

    /**
     * Only created_at exists, no updated_at in the existing schema.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'title',
        'allow_feedback',
        'reading_text',
        'allow_reading',
    ];

    protected function casts(): array
    {
        return [
            'allow_feedback' => 'boolean',
            'allow_reading'  => 'boolean',
            'created_at'     => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    public function triplets(): HasMany
    {
        return $this->hasMany(Triplet::class, 'map_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class, 'map_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class, 'map_id');
    }

    public function testScores(): HasMany
    {
        return $this->hasMany(StudentTestScore::class, 'cer_map_id');
    }
}
