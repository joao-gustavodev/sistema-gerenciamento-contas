<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('manageUsers', User::class);

        return view('users.index', [
            'users' => User::query()->withCount(['accounts', 'postedTransactions'])->orderBy('name')->paginate(15),
            'roles' => User::ROLES,
        ]);
    }

    public function create(): View
    {
        $this->authorize('manageUsers', User::class);

        return view('users.create', [
            'roles' => User::ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manageUsers', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:254', 'unique:users,email'],
            'role' => ['required', 'string', Rule::in(array_keys(User::ROLES))],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        User::query()->create($validated);

        return redirect()->route('users.index')
            ->with('success', 'Usuário criado com sucesso.');
    }

    public function edit(User $user): View
    {
        $this->authorize('manageUsers', User::class);

        return view('users.edit', [
            'user' => $user,
            'roles' => User::ROLES,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('manageUsers', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:254', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'string', Rule::in(array_keys(User::ROLES))],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
        ]);

        DB::transaction(function () use ($request, $user, $validated): void {
            $this->guardRoleChange($request, $user, $validated['role']);

            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
            ]);

            if (filled($validated['password'] ?? null)) {
                $user->password = $validated['password'];
            }

            $user->save();
        });

        return redirect()->route('users.index')
            ->with('success', 'Usuário atualizado com sucesso.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('manageUsers', User::class);

        if ($request->user()->is($user)) {
            throw ValidationException::withMessages([
                'user' => 'Não é possível excluir a própria conta.',
            ]);
        }

        DB::transaction(function () use ($request, $user): void {
            $this->guardRoleChange($request, $user, null);

            if ($user->accounts()->lockForUpdate()->exists() || $user->postedTransactions()->lockForUpdate()->exists()) {
                throw ValidationException::withMessages([
                    'user' => 'Não é possível excluir um usuário vinculado a contas ou lançamentos financeiros.',
                ]);
            }

            $user->delete();
        });

        return redirect()->route('users.index')
            ->with('success', 'Usuário excluído com sucesso.');
    }

    private function guardRoleChange(Request $request, User $user, ?string $newRole): void
    {
        if ($request->user()->is($user) && $user->role !== $newRole) {
            throw ValidationException::withMessages([
                'role' => 'Não é possível alterar o próprio papel.',
            ]);
        }

        if ($user->isAdmin() && $newRole !== User::ROLE_ADMIN) {
            $remainingAdmins = User::query()
                ->where('role', User::ROLE_ADMIN)
                ->lockForUpdate()
                ->count();

            if ($remainingAdmins <= 1) {
                throw ValidationException::withMessages([
                    'role' => 'O sistema precisa manter pelo menos um administrador.',
                ]);
            }
        }
    }
}
