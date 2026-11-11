<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Triplet extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'map_id',
        'claim',
        'evidence',
        'reasoning',
    ];

    // ── Relationships ──────────────────────────────────────────

    public function cerMap(): BelongsTo
    {
        return $this->belongsTo(CerMap::class, 'map_id');
    }
}
