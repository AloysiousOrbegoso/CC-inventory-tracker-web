<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafetyChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'completed_by',
        'check_date',
        'status',
        'items',
        'overall_notes',
    ];

    protected $casts = [
        'items' => 'array',
        'check_date' => 'date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Default checklist items for F&B businesses
     */
    public static function defaultItems(): array
    {
        return [
            ['item' => 'Handwashing stations stocked and accessible', 'passed' => false, 'notes' => ''],
            ['item' => 'Food storage temperatures verified (cold < 4°C, frozen < -18°C)', 'passed' => false, 'notes' => ''],
            ['item' => 'Allergen information displayed clearly', 'passed' => false, 'notes' => ''],
            ['item' => 'Pest control measures in place', 'passed' => false, 'notes' => ''],
            ['item' => 'Fire extinguishers inspected and accessible', 'passed' => false, 'notes' => ''],
            ['item' => 'First aid kit stocked and accessible', 'passed' => false, 'notes' => ''],
            ['item' => 'Emergency exits clearly marked and unobstructed', 'passed' => false, 'notes' => ''],
            ['item' => 'Food contact surfaces properly sanitized', 'passed' => false, 'notes' => ''],
            ['item' => 'Personal protective equipment available', 'passed' => false, 'notes' => ''],
            ['item' => 'Waste disposal areas clean and organized', 'passed' => false, 'notes' => ''],
            ['item' => 'Chemical storage properly labeled and separated', 'passed' => false, 'notes' => ''],
            ['item' => 'Ventilation systems functioning properly', 'passed' => false, 'notes' => ''],
        ];
    }
}
