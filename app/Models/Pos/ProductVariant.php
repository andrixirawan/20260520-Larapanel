<?php

namespace App\Models\Pos;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'product_id',
    'name',
    'sku',
    'barcode',
    'price',
    'cost_price',
    'track_inventory',
    'allow_backorder',
    'is_default',
    'metadata',
])]
class ProductVariant extends Model
{
    use SoftDeletes;

    protected $table = 'pos_product_variants';

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'track_inventory' => 'boolean',
            'allow_backorder' => 'boolean',
            'is_default' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function stock(): HasOne
    {
        return $this->hasOne(InventoryStock::class, 'product_variant_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'product_variant_id');
    }
}
