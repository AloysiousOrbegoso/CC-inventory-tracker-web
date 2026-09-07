<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Equipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'category',
        'serial_number',
        'purchase_date',
        'warranty_expiry',
        'last_maintenance',
        'next_maintenance',
        'status',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'last_maintenance' => 'date',
        'next_maintenance' => 'date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getDaysUntilMaintenanceAttribute(): ?int
    {
        if (! $this->next_maintenance) return null;
        return max(0, now()->diffInDays($this->next_maintenance, false));
    }

    public function getIsMaintenanceDueAttribute(): bool
    {
        return $this->next_maintenance && $this->next_maintenance->lte(now());
    }
}
