<label class="lb-field">
    <span class="lb-label">{{ $label }}</span>
    <select wire:model="bank_id" class="lb-control">
        <option value="">Sin indicar</option>
        @foreach($banks as $bank)
            <option value="{{ $bank->id }}">{{ $bank->name }}</option>
        @endforeach
    </select>
    @error('bank_id') <span class="lb-error">{{ $message }}</span> @enderror
    @if($banks->isEmpty())
        <span class="lb-help">Agrégalos en <a href="{{ route('profile') }}" wire:navigate>Cuenta</a>.</span>
    @endif
</label>
