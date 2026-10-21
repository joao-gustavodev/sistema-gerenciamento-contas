<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\RevenueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Revenue extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pendente',
        self::STATUS_RECEIVED => 'Recebida',
        self::STATUS_CANCELLED => 'Cancelada',
    ];

    /** @use HasFactory<RevenueFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'name',
        'amount_minor',
        'expected_date',
        'source',
        'notes',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'expected_date' => 'date',
            'received_at' => 'datetime',
        ];
    }

    public function getAmountAttribute(): string
    {
        return Money::format((int) $this->amount_minor);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function accountTransaction(): BelongsTo
    {
        return $this->belongsTo(AccountTransaction::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(RevenueHistory::class)->latest('id');
    }
}
