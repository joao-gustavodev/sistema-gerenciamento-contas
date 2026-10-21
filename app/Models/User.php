<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_EDITOR = 'editor';

    public const ROLE_VIEWER = 'viewer';

    public const ROLES = [
        self::ROLE_ADMIN => 'Administrador',
        self::ROLE_EDITOR => 'Editor',
        self::ROLE_VIEWER => 'Somente visualização',
    ];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function accounts(): HasMany
    {
        return $this->hasMany(FinancialAccount::class, 'created_by');
    }

    public function revenues(): HasMany
    {
        return $this->hasMany(Revenue::class);
    }

    public function postedTransactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class, 'posted_by');
    }

    public function visibleAccounts(): Builder
    {
        return FinancialAccount::query()->where(function (Builder $query): void {
            $query->where('created_by', $this->getKey())
                ->orWhereIn('created_by', $this->sharesReceived()
                    ->where('status', UserShare::STATUS_ACTIVE)
                    ->select('owner_id'));
        });
    }

    public function sharesGranted(): HasMany
    {
        return $this->hasMany(UserShare::class, 'owner_id');
    }

    public function sharesReceived(): HasMany
    {
        return $this->hasMany(UserShare::class, 'recipient_id');
    }

    public function canViewAccount(FinancialAccount $account): bool
    {
        if ((int) $account->created_by === (int) $this->getKey()) {
            return true;
        }

        return $this->activeShareForAccount($account) !== null;
    }

    public function canEditAccount(FinancialAccount $account): bool
    {
        if ((int) $account->created_by === (int) $this->getKey()) {
            return $this->canEditAccounts();
        }

        return $this->activeShareForAccount($account)?->permission === UserShare::PERMISSION_EDIT;
    }

    private function activeShareForAccount(FinancialAccount $account): ?UserShare
    {
        if ($this->relationLoaded('sharesReceived')) {
            return $this->sharesReceived->first(fn (UserShare $share): bool => (int) $share->owner_id === (int) $account->created_by
                && $share->status === UserShare::STATUS_ACTIVE);
        }

        return $this->sharesReceived()
            ->where('owner_id', $account->created_by)
            ->where('status', UserShare::STATUS_ACTIVE)
            ->first();
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function canEditAccounts(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_EDITOR], true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
