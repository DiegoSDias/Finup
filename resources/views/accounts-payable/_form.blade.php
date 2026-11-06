<div class="space-y-5">
    <div>
        <label class="block text-sm font-medium text-slate-700">Descrição</label>
        <input name="description" value="{{ old('description', $payable->description ?? '') }}" required maxlength="255" class="mt-1 w-full rounded-xl border-slate-300" placeholder="Ex.: Conta de energia">
        @error('description') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-slate-700">Valor</label>
            <input name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount', isset($payable) ? number_format((float) $payable->amount, 2, '.', '') : '') }}" required class="mt-1 w-full rounded-xl border-slate-300">
            @error('amount') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Vencimento</label>
            <input name="due_date" type="date" value="{{ old('due_date', isset($payable) ? $payable->due_date->format('Y-m-d') : '') }}" required class="mt-1 w-full rounded-xl border-slate-300">
            @error('due_date') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Categoria</label>
        <select name="category_id" class="mt-1 w-full rounded-xl border-slate-300">
            <option value="">Sem categoria</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $payable->category_id ?? '') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">Uma categoria é necessária para registrar um pagamento manual.</p>
        @error('category_id') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>
</div>
