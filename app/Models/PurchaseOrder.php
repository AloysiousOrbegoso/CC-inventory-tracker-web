<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'branch_id',
        'supplier_id',
        'created_by',
        'status',
        'total_amount',
        'expected_delivery',
        'delivered_at',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'expected_delivery' => 'date',
        'delivered_at' => 'date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function recalculateTotal(): void
    {
        $total = $this->items()->sum(DB::raw('quantity * unit_price'));
        $this->update(['total_amount' => $total]);
    }

    public static function generatePoNumber(): string
    {
        $lastPo = self::orderBy('id', 'desc')->first();
        $number = $lastPo ? intval(substr($lastPo->po_number, 3)) + 1 : 1;
        return 'PO-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }
}
