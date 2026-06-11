<?php

namespace App\Models\Pos;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'entry_date',
    'shift_id',
    'source_type',
    'source_id',
    'type',
    'direction',
    'payment_method',
    'amount',
    'created_by',
    'notes',
    'metadata',
])]
class FinanceEntry extends Model
{
    use HasPublicId;

    public const TYPE_SALE_INCOME = 'sale_income';

    public const TYPE_SALE_VOID = 'sale_void';

    public const DIRECTION_CREDIT = 'credit';

    public const DIRECTION_DEBIT = 'debit';

    protected $table = 'pos_finance_entries';

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
