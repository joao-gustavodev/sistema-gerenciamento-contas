<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class FinancialAccount extends Model
{
    public const TYPES = [
        'checking' => 'Corrente',
        'savings' => 'Poupança',
        'investment' => 'Investimento',
        'payment' => 'Pagamento',
        'other' => 'Outra',
    ];

    protected $fillable = [
        'name',
        'bank_name',
        'branch',
        'account_number',
        'type',
        'currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected function performUpdate(Builder $query)
    {
        if ($this->isDirty('opening_balance_minor')) {
            throw new LogicException('The opening balance cannot be changed after the account is created.');
        }

        return parent::performUpdate($query);
    }

    public function getCurrentBalanceAttribute(): string
    {
        return Money::format($this->current_balance_minor);
    }

    public function getCurrentBalanceMinorAttribute(): int
    {
        $credits = (int) ($this->attributes['credits_total']
            ?? $this->transactions()->where('type', AccountTransaction::TYPE_CREDIT)->sum('amount_minor'));
        $debits = (int) ($this->attributes['debits_total']
            ?? $this->transactions()->where('type', AccountTransaction::TYPE_DEBIT)->sum('amount_minor'));

        return (int) $this->opening_balance_minor + $credits - $debits;
    }

    public function getOpeningBalanceAttribute(): string
    {
        return Money::format((int) $this->opening_balance_minor);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class, 'financial_account_id');
    }
}
