<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GameMatch extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'matches';

    protected $fillable = [
        'booking_id',
        'club_id',
        'court_id',
        'match_type',
        'status',
        'team1_players',
        'team2_players',
        'team1_score',
        'team2_score',
        'score_details',
        'winner_team',
        'is_competitive',
        'skill_level_min',
        'skill_level_max',
    ];

    protected $casts = [
        'team1_players' => 'array',
        'team2_players' => 'array',
        'score_details' => 'array',
        'is_competitive' => 'boolean',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(MatchPlayer::class, 'match_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'match_players', 'match_id')
            ->withPivot('team', 'status')
            ->withTimestamps();
    }
}
