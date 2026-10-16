<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

class AccountTransaction extends Model
{
    public const UPDATED_AT = null;

    public const TYPE_CREDIT = 'credit';

    public const TYPE_DEBIT = 'debit';

    protected $fillable = [
        'type',
        'amount_minor',
        'description',
    ];

    protected function performUpdate(Builder $query)
    {
        throw new LogicException('Account transactions are immutable; post a reversal instead.');
    }

    protected function performDeleteOnModel()
    {
        throw new LogicException('Account transactions cannot be deleted; post a reversal instead.');
    }

    public function getAmountAttribute(): string
    {
        return Money::format((int) $this->amount_minor);
    }

    public function getIsReversedAttribute(): bool
    {
        return $this->reversal()->exists();
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function originalTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_id');
    }
}
