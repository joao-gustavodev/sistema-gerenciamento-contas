<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserShare;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserShareController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('sharing.index', [
            'shares' => $user->sharesGranted()
                ->with(['recipient', 'grantor'])
                ->latest('granted_at')
                ->paginate(15),
            'recipients' => User::query()
                ->where('id', '!=', $user->getKey())
                ->whereDoesntHave('sharesReceived', fn ($query) => $query
                    ->where('owner_id', $user->getKey())
                    ->where('status', UserShare::STATUS_ACTIVE))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'permissions' => UserShare::PERMISSIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'recipient_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::notIn([$user->getKey()]),
            ],
            'permission' => [
                'required',
                'string',
                Rule::in(array_keys(UserShare::PERMISSIONS)),
            ],
        ]);

        $user->sharesGranted()->updateOrCreate(
            ['recipient_id' => $validated['recipient_id']],
            [
                'granted_by_id' => $user->getKey(),
                'permission' => $validated['permission'],
                'status' => UserShare::STATUS_ACTIVE,
                'granted_at' => now(),
                'revoked_at' => null,
            ],
        );

        return redirect()->route('sharing.index')
            ->with('success', 'Acesso compartilhado com sucesso.');
    }

    public function update(Request $request, UserShare $userShare): RedirectResponse
    {
        $share = $request->user()->sharesGranted()
            ->whereKey($userShare->getKey())
            ->where('status', UserShare::STATUS_ACTIVE)
            ->firstOrFail();

        $validated = $request->validate([
            'permission' => [
                'required',
                'string',
                Rule::in(array_keys(UserShare::PERMISSIONS)),
            ],
        ]);

        $share->update([
            'permission' => $validated['permission'],
        ]);

        return redirect()->route('sharing.index')
            ->with('success', 'Permissão atualizada com sucesso.');
    }

    public function destroy(Request $request, UserShare $userShare): RedirectResponse
    {
        $share = $request->user()->sharesGranted()
            ->whereKey($userShare->getKey())
            ->where('status', UserShare::STATUS_ACTIVE)
            ->firstOrFail();

        $share->update([
            'status' => UserShare::STATUS_REVOKED,
            'revoked_at' => now(),
        ]);

        return redirect()->route('sharing.index')
            ->with('success', 'Acesso revogado com sucesso.');
    }
}
