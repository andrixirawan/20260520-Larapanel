<?php

namespace App\Models\Pos;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'product_variant_id',
    'actor_id',
    'type',
    'quantity_before',
    'quantity_delta',
    'quantity_after',
    'reference_type',
    'reference_id',
    'notes',
    'metadata',
])]
class InventoryMovement extends Model
{
    public const TYPE_OPENING = 'opening';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_STOCK_OPNAME = 'stock_opname';

    public const TYPE_SALE = 'sale';

    public const TYPE_SALE_VOID = 'sale_void';

    protected $table = 'pos_inventory_movements';

    protected function casts(): array
    {
        return [
            'quantity_before' => 'decimal:3',
            'quantity_delta' => 'decimal:3',
            'quantity_after' => 'decimal:3',
            'metadata' => 'array',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
