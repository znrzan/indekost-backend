<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class Tenant extends Model
{
    /** @use HasFactory<\Database\Factories\TenantFactory> */
    use HasFactory, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'room_id',
        'name',
        'whatsapp_number',
        'entry_date',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
        ];
    }

    /**
     * Get the room that the tenant belongs to.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the payments for the tenant.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get formatted WhatsApp number with country code.
     */
    public function getFormattedWhatsappAttribute(): string
    {
        $number = $this->whatsapp_number;
        
        // If number starts with 0, replace with 62
        if (str_starts_with($number, '0')) {
            $number = '62' . substr($number, 1);
        }
        
        // If number doesn't start with 62, add it
        if (!str_starts_with($number, '62')) {
            $number = '62' . $number;
        }
        
        return $number;
    }

    /**
     * Scope a query to only include active tenants.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include inactive tenants.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Get the owner of this tenant (through room).
     */
    public function getOwnerAttribute()
    {
        return $this->room ? $this->room->owner : null;
    }

    /**
     * Get the owner ID of this tenant (through room).
     */
    public function getOwnerIdAttribute()
    {
        return $this->room ? $this->room->owner_id : null;
    }
}
