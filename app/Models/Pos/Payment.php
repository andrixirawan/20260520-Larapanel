<?php

namespace App\Models\Pos;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sale_id',
    'method',
    'status',
    'amount',
    'received_amount',
    'change_amount',
    'provider',
    'provider_reference',
    'metadata',
])]
class Payment extends Model
{
    use HasPublicId;

    public const METHOD_CASH = 'cash';

    public const METHOD_QRIS_DUMMY = 'qris_dummy';

    public const METHOD_CARD_DUMMY = 'card_dummy';

    public const METHOD_BANK_TRANSFER_DUMMY = 'bank_transfer_dummy';

    public const METHOD_EWALLET_DUMMY = 'ewallet_dummy';

    public const STATUS_PAID = 'paid';

    public const STATUS_REFUNDED = 'refunded';

    protected $table = 'pos_payments';

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'received_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    /**
     * @return array<string, string>
     */
    public static function methodOptions(): array
    {
        return [
            self::METHOD_CASH => 'Cash',
            self::METHOD_QRIS_DUMMY => 'QRIS Dummy',
            self::METHOD_CARD_DUMMY => 'Card Dummy',
            self::METHOD_BANK_TRANSFER_DUMMY => 'Bank Transfer Dummy',
            self::METHOD_EWALLET_DUMMY => 'E-Wallet Dummy',
        ];
    }
}
