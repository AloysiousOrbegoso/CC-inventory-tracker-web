<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'location', 'street_address', 'city', 'province', 'zip_code', 'phone', 'email', 'latitude', 'longitude', 'status', 'description', 'services'])]
class Branch extends Model
{
    use HasFactory;

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(BranchStock::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function shiftLogs(): HasMany
    {
        return $this->hasMany(ShiftLog::class);
    }

    public function discrepancyAlerts(): HasMany
    {
        return $this->hasMany(DiscrepancyAlert::class);
    }
}
