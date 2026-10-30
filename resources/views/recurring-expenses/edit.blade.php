<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
        <div class="mx-auto max-w-3xl px-4">
            <a href="{{ route('recurring-expenses.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">← Voltar</a>
            <div class="mt-5 rounded-2xl border border-slate-100 bg-white p-8 shadow-sm">
                <p class="text-sm font-medium uppercase tracking-widest text-rose-600">Despesas recorrentes</p>
                <h1 class="mt-2 text-3xl font-light text-slate-900">Editar recorrência</h1>
                <p class="mt-2 text-sm text-slate-500">As despesas já ocorridas não são modificadas. Valor, categoria, banco e forma de pagamento passam a valer nas próximas ocorrências.</p>

                <form action="{{ route('recurring-expenses.update', $recurringExpense) }}" method="POST" class="mt-8 grid gap-5 md:grid-cols-2">
                    @csrf
                    @method('PUT')

                    <div class="md:col-span-2">
                        <label class="text-sm font-medium">Descrição</label>
                        <input name="description" value="{{ old('description', $recurringExpense->description) }}" maxlength="255" class="mt-1 w-full rounded-xl border-slate-200">
                    </div>
                    <div>
                        <label class="text-sm font-medium">Valor de cada ocorrência</label>
                        <input name="amount" type="number" min="0.01" step="0.01" required value="{{ old('amount', $recurringExpense->amount) }}" class="mt-1 w-full rounded-xl border-slate-200">
                        <p class="mt-1 text-xs text-slate-500">O novo valor não altera despesas já ocorridas.</p>
                        @error('amount')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium">Periodicidade</label>
                        <select name="frequency" required class="mt-1 w-full rounded-xl border-slate-200">
                            @foreach(['monthly' => 'Mensal', 'quarterly' => 'Trimestral', 'semiannual' => 'Semestral', 'annual' => 'Anual'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('frequency', $recurringExpense->frequency) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Nova data-base</label>
                        <input name="date" type="date" required value="{{ old('date', $recurringExpense->start_date->format('Y-m-d')) }}" class="mt-1 w-full rounded-xl border-slate-200">
                        <p class="mt-1 text-xs text-slate-500">Reorganiza somente as próximas ocorrências.</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Encerrar em (opcional)</label>
                        <input name="end_date" type="date" value="{{ old('end_date', $recurringExpense->end_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-xl border-slate-200">
                    </div>
                    <div>
                        <label class="text-sm font-medium">Categoria</label>
                        <select name="category_id" required class="mt-1 w-full rounded-xl border-slate-200">
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $recurringExpense->category_id) == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Forma de pagamento</label>
                        <select name="payment_mode" required class="mt-1 w-full rounded-xl border-slate-200">
                            @foreach(['pix' => 'Pix', 'dinheiro' => 'Dinheiro', 'debito' => 'Cartão de débito', 'credito' => 'Cartão de crédito'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_mode', $recurringExpense->payment_mode) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @php($bankChoice = $recurringExpense->bank_status === 'bank' ? (string) $recurringExpense->manual_bank_id : 'none')
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium">Banco da movimentação</label>
                        <select name="bank_choice" required class="mt-1 w-full rounded-xl border-slate-200">
                            <option value="none" @selected(old('bank_choice', $bankChoice) === 'none')>Não realizado por meio de um banco</option>
                            @foreach($banks as $bank)
                                <option value="{{ $bank->id }}" @selected((string) old('bank_choice', $bankChoice) === (string) $bank->id)>{{ $bank->name }}</option>
                            @endforeach
                            <option value="new" @selected(old('bank_choice') === 'new')>Cadastrar outro banco</option>
                        </select>
                        <input name="new_bank" maxlength="100" value="{{ old('new_bank') }}" placeholder="Nome do novo banco, se selecionado" class="mt-2 w-full rounded-xl border-slate-200">
                        @error('bank_choice')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2 flex justify-end gap-3 border-t pt-6">
                        <a href="{{ route('recurring-expenses.index') }}" class="rounded-xl border px-5 py-3">Cancelar</a>
                        <button class="rounded-xl bg-rose-600 px-5 py-3 font-medium text-white">Atualizar recorrência</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
