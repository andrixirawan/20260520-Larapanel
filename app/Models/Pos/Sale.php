<?php

namespace App\Models\Pos;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'shift_id',
    'cashier_id',
    'invoice_number',
    'status',
    'payment_status',
    'subtotal',
    'discount_total',
    'tax_total',
    'total',
    'paid_total',
    'change_total',
    'customer_name',
    'notes',
    'completed_at',
    'voided_by',
    'voided_at',
    'void_reason',
    'metadata',
])]
class Sale extends Model
{
    use HasPublicId;

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_VOIDED = 'voided';

    public const PAYMENT_STATUS_PAID = 'paid';

    public const PAYMENT_STATUS_PENDING = 'pending';

    protected $table = 'pos_sales';

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_total' => 'decimal:2',
            'change_total' => 'decimal:2',
            'completed_at' => 'datetime',
            'voided_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'sale_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'sale_id');
    }
}
