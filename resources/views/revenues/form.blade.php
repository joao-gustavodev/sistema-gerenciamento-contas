<div class="form-grid">
    <div class="full">
        <label for="name">Descrição da receita</label>
        <input id="name" name="name" value="{{ old('name', $revenue->name) }}" required maxlength="120">
        @error('name') <p class="error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="amount">Valor</label>
        <input id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount', $revenue->exists ? $revenue->amount : '') }}" required>
        @error('amount') <p class="error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="currency">Moeda (código ISO 4217)</label>
        @if ($revenue->received_at)
            <input id="currency" value="{{ $revenue->currency }}" disabled>
            <input type="hidden" name="currency" value="{{ $revenue->currency }}">
            <small>A moeda não pode ser alterada depois do recebimento.</small>
        @else
            <input id="currency" name="currency" value="{{ old('currency', $revenue->currency ?: 'BRL') }}" required minlength="3" maxlength="3" pattern="[A-Z]{3}">
        @endif
        @error('currency') <p class="error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="expected_date">Data prevista</label>
        <input id="expected_date" name="expected_date" type="date" value="{{ old('expected_date', $revenue->exists ? $revenue->expected_date->toDateString() : '') }}" required>
        @error('expected_date') <p class="error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="source">Fonte de renda (opcional)</label>
        <input id="source" name="source" value="{{ old('source', $revenue->source) }}" maxlength="120">
        @error('source') <p class="error">{{ $message }}</p> @enderror
    </div>
    <div class="full">
        <label for="notes">Observações (opcional)</label>
        <textarea id="notes" name="notes" rows="4" maxlength="2000">{{ old('notes', $revenue->notes) }}</textarea>
        @error('notes') <p class="error">{{ $message }}</p> @enderror
    </div>
</div>
