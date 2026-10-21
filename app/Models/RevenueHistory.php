<?php

namespace App\Models;

use Database\Factories\RevenueHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueHistory extends Model
{
    public const UPDATED_AT = null;

    /** @use HasFactory<RevenueHistoryFactory> */
    use HasFactory;

    protected $fillable = [
        'revenue_id',
        'changed_by',
        'action',
        'changes',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    public function revenue(): BelongsTo
    {
        return $this->belongsTo(Revenue::class)->withTrashed();
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
