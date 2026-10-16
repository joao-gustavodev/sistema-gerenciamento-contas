<div class="form-grid">
    <div>
        <label for="name">Nome identificador</label>
        <input id="name" name="name" value="{{ old('name', $account?->name) }}" required maxlength="120">
        @error('name') <p class="error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="bank_name">Banco ou instituição</label>
        <input id="bank_name" name="bank_name" value="{{ old('bank_name', $account?->bank_name) }}" required maxlength="120">
        @error('bank_name') <p class="error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="branch">Agência</label>
        <input id="branch" name="branch" value="{{ old('branch', $account?->branch) }}" maxlength="40">
        @error('branch') <p class="error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="account_number">Número da conta</label>
        <input id="account_number" name="account_number" value="{{ old('account_number', $account?->account_number) }}" required maxlength="64">
        @error('account_number') <p class="error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="type">Tipo de conta</label>
        <select id="type" name="type" required>
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $account?->type ?? 'checking') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('type') <p class="error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="currency">Moeda (código ISO 4217)</label>
        <input id="currency" name="currency" value="{{ old('currency', $account?->currency ?? 'BRL') }}" required minlength="3" maxlength="3" pattern="[A-Z]{3}" @disabled($account)>
        @if ($account)
            <input type="hidden" name="currency" value="{{ $account->currency }}">
            <small>A moeda não pode ser alterada após a criação.</small>
        @endif
        @error('currency') <p class="error">{{ $message }}</p> @enderror
    </div>
    @if (! $account)
        <div>
            <label for="opening_balance">Saldo inicial</label>
            <input id="opening_balance" name="opening_balance" type="number" step="0.01" value="{{ old('opening_balance', '0.00') }}" required>
            <small>Este valor é imutável após a criação; mudanças de saldo devem ser lançamentos.</small>
            @error('opening_balance') <p class="error">{{ $message }}</p> @enderror
        </div>
    @else
        <div>
            <label for="is_active">Situação</label>
            <select id="is_active" name="is_active" required>
                <option value="1" @selected((string) old('is_active', (int) $account->is_active) === '1')>Ativa</option>
                <option value="0" @selected((string) old('is_active', (int) $account->is_active) === '0')>Arquivada</option>
            </select>
            <small>Contas com histórico são arquivadas, nunca excluídas.</small>
            @error('is_active') <p class="error">{{ $message }}</p> @enderror
        </div>
    @endif
</div>
