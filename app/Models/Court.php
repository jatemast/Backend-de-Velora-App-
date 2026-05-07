<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Court extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'club_id',
        'name',
        'description',
        'surface_type',
        'court_type',
        'is_covered',
        'has_lighting',
        'max_players',
        'price_per_hour',
        'price_per_session',
        'photos',
        'amenities',
        'is_active',
    ];

    protected $casts = [
        'is_covered' => 'boolean',
        'has_lighting' => 'boolean',
        'is_active' => 'boolean',
        'photos' => 'array',
        'amenities' => 'array',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function gameMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }
}
