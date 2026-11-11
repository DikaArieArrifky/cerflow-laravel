<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTestScore extends Model
{
    protected $fillable = [
        'user_id',
        'cer_map_id',
        'pre_test',
        'post_test',
        'delayed_test',
    ];

    protected function casts(): array
    {
        return [
            'pre_test'     => 'float',
            'post_test'    => 'float',
            'delayed_test' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cerMap(): BelongsTo
    {
        return $this->belongsTo(CerMap::class, 'cer_map_id');
    }

    /**
     * Selisih nilai = post_test - pre_test (Gain Score).
     */
    public function getScoreDiffAttribute(): ?float
    {
        if ($this->post_test !== null && $this->pre_test !== null) {
            return round((float) $this->post_test - (float) $this->pre_test, 2);
        }

        return null;
    }
}

