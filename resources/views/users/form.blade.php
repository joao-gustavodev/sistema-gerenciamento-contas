<div class="form-grid">
    <div>
        <label for="name">Nome</label>
        <input id="name" name="name" value="{{ old('name', $user?->name) }}" required maxlength="255" autocomplete="name">
        @error('name') <p class="error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="email">E-mail</label>
        <input id="email" name="email" type="email" value="{{ old('email', $user?->email) }}" required maxlength="254" autocomplete="email">
        @error('email') <p class="error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="role">Papel</label>
        <select id="role" name="role" required @disabled($user && auth()->user()->is($user))>
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $user?->role ?? \App\Models\User::ROLE_VIEWER) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @if ($user && auth()->user()->is($user))
            <input type="hidden" name="role" value="{{ $user->role }}">
            <small>Seu próprio papel não pode ser alterado.</small>
        @endif
        @error('role') <p class="error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password">Senha @if ($user) (opcional para manter a atual) @endif</label>
        <input id="password" name="password" type="password" @required(! $user) minlength="12" autocomplete="new-password">
        @error('password') <p class="error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password_confirmation">Confirme a senha</label>
        <input id="password_confirmation" name="password_confirmation" type="password" @required(! $user) minlength="12" autocomplete="new-password">
    </div>
</div>
