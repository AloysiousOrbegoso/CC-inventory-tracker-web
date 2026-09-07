<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'contact_person', 'contact_number', 'address', 'latitude', 'longitude', 'landmark', 'photo_path', 'notes', 'is_active'])]
class Supplier extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class)
            ->withPivot('unit_cost', 'is_primary')
            ->withTimestamps();
    }

    public function purchaseHistory(): HasMany
    {
        return $this->hasMany(PurchaseHistory::class);
    }
}
