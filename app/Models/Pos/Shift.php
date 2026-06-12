<?php

namespace App\Models\Pos;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'cashier_id',
    'opened_by',
    'closed_by',
    'handover_to_cashier_id',
    'handover_requested_by',
    'handover_approved_by',
    'status',
    'opening_cash',
    'expected_cash',
    'counted_cash',
    'cash_difference',
    'opened_at',
    'closed_at',
    'handover_requested_at',
    'handover_approved_at',
    'notes',
    'handover_notes',
    'metadata',
])]
class Shift extends Model
{
    use HasPublicId;

    public const STATUS_OPEN = 'open';

    public const STATUS_HANDOVER_PENDING = 'handover_pending';

    public const STATUS_CLOSED = 'closed';

    protected $table = 'pos_shifts';

    protected function casts(): array
    {
        return [
            'opening_cash' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'counted_cash' => 'decimal:2',
            'cash_difference' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'handover_requested_at' => 'datetime',
            'handover_approved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function handoverToCashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handover_to_cashier_id');
    }

    public function handoverRequestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handover_requested_by');
    }

    public function handoverApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handover_approved_by');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'shift_id');
    }
}
