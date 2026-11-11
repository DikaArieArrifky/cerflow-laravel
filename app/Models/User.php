<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    /**
     * Disable default Laravel timestamps (users table has no created_at/updated_at).
     */
    public $timestamps = false;

    protected $fillable = [
        'username',
        'namalengkap',
        'password',
        'role',
        'gemini_api_key',
    ];

    protected $hidden = [
        'password',
        'gemini_api_key',
    ];

    public function setPasswordAttribute($value): void
    {
        if (is_string($value) && (str_starts_with($value, '$2y$') || str_starts_with($value, '$2a$') || str_starts_with($value, '$2b$'))) {
            $this->attributes['password'] = $value;
        } else {
            $this->attributes['password'] = \Illuminate\Support\Facades\Hash::make($value);
        }
    }

    // ── Relationships ──────────────────────────────────────────

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    public function testScores(): HasMany
    {
        return $this->hasMany(StudentTestScore::class);
    }

    // ── Helpers ────────────────────────────────────────────────

    public function isGuru(): bool
    {
        return $this->role === 'guru';
    }

    public function isSiswa(): bool
    {
        return $this->role === 'siswa';
    }
}
