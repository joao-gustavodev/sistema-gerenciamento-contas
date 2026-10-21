<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['owner_id', 'recipient_id', 'granted_by_id', 'permission', 'status', 'granted_at', 'revoked_at'])]
class UserShare extends Model
{
    public const PERMISSION_VIEW = 'view';

    public const PERMISSION_EDIT = 'edit';

    public const PERMISSIONS = [
        self::PERMISSION_VIEW => 'Visualização',
        self::PERMISSION_EDIT => 'Edição',
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVOKED = 'revoked';

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function grantor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_id');
    }
}
