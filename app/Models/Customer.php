<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'email',
        'phone',
        'loyalty_points',
        'tier',
        'total_spent',
        'total_visits',
        'last_visit_at',
    ];

    protected $casts = [
        'total_spent' => 'decimal:2',
        'last_visit_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(CustomerFeedback::class);
    }

    public function addLoyaltyPoints(int $points): void
    {
        $this->increment('loyalty_points', $points);
        $this->updateTier();
    }

    private function updateTier(): void
    {
        $tier = match(true) {
            $this->loyalty_points >= 1000 => 'platinum',
            $this->loyalty_points >= 500 => 'gold',
            $this->loyalty_points >= 200 => 'silver',
            default => 'bronze',
        };
        $this->update(['tier' => $tier]);
    }
}
