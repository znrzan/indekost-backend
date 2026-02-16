<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Meter extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'room_id',
        'owner_id',
        'type',
        'last_value',
        'threshold',
        'unit',
        'updated_by',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'last_value' => 'decimal:2',
            'threshold' => 'decimal:2',
        ];
    }

    /**
     * Get the room that owns the meter.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the owner of the meter.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Scope a query to filter by owner.
     */
    public function scopeForOwner($query, $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    /**
     * Scope a query to only include meters with low balance.
     */
    public function scopeLowBalance($query)
    {
        return $query->whereRaw('last_value <= threshold');
    }

    /**
     * Scope a query to filter by meter type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if meter balance is low.
     */
    public function getIsLowAttribute(): bool
    {
        return $this->last_value <= $this->threshold;
    }
}
