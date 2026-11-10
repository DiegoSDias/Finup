<x-app-layout>
    @php($isIncome = $type === 'income')
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
        <div class="max-w-3xl mx-auto px-4">
            <a href="{{ $source === 'pluggy' ? route('pluggy.transactions.show', $transaction) : route('expenses.show', $transaction) }}" class="group inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 transition-colors mb-4">← Voltar aos detalhes</a>
            <div class="mb-6 flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl {{ $isIncome ? 'bg-gradient-to-br from-emerald-500 to-green-500' : 'bg-gradient-to-br from-rose-500 to-red-500' }} flex items-center justify-center shadow-lg"></div>
                <div><p class="text-sm font-medium uppercase tracking-widest {{ $source === 'pluggy' ? 'text-blue-600' : 'text-slate-600' }}">{{ $source === 'pluggy' ? 'Importado pela Pluggy' : 'Lançamento manual' }}</p><h1 class="text-3xl font-light text-slate-900">Editar {{ $isIncome ? 'receita' : 'despesa' }}</h1></div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 animate-slide-up">
                @if ($source === 'manual')
                    <form action="{{ route('expenses.update', $transaction) }}" method="POST">
                        @csrf @method('PUT')
                        @if ($transaction->installments_group)
                            <label for="installmentScope" class="block text-sm font-semibold text-slate-700 mb-2">Aplicar alterações a</label>
                            <select id="installmentScope" name="installment_scope" class="w-full rounded-xl border-slate-200 mb-4"><option value="single" @selected(old('installment_scope', 'single') === 'single')>Somente esta parcela</option><option value="group" @selected(old('installment_scope') === 'group')>Todo o grupo de parcelas</option></select>
                        @endif
                        @if ($transaction->recurring_expense_id && $recurringExpense?->is_active)
                            <label for="recurrenceScope" class="block text-sm font-semibold text-slate-700 mb-2">Aplicar alterações a</label>
                            <select id="recurrenceScope" name="recurrence_scope" class="w-full rounded-xl border-slate-200 mb-4"><option value="single" @selected(old('recurrence_scope', 'single') === 'single')>Somente esta ocorrência</option><option value="future" @selected(old('recurrence_scope') === 'future')>Esta e as próximas</option></select>
                        @endif
                        @include('expenses._form', ['expense' => $transaction, 'type' => $type])
                        <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-slate-200"><a href="{{ route('expenses.show', $transaction) }}" class="px-6 py-3 rounded-xl bg-white border border-slate-200 text-slate-700 font-medium">Cancelar</a><button class="px-6 py-3 rounded-xl {{ $isIncome ? 'bg-emerald-600' : 'bg-rose-600' }} text-white font-medium">Atualizar</button></div>
                    </form>
                @else
                    <p class="mb-6 text-sm text-slate-500">Suas alterações serão preservadas nas próximas sincronizações. O valor vem da instituição e não pode ser alterado aqui.</p>
                    <form method="POST" action="{{ route('pluggy.transactions.update', $transaction) }}" class="space-y-6">
                        @csrf @method('PUT')
                        <div><label class="block text-sm font-semibold text-slate-700 mb-2">Descrição</label><input name="description" value="{{ old('description', $transaction->description) }}" required maxlength="500" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl"><x-input-error :messages="$errors->get('description')" class="mt-2" /></div>
                        <div><label class="block text-sm font-semibold text-slate-700 mb-2">Data</label><input type="date" name="transaction_date" value="{{ old('transaction_date', $transaction->transaction_date->format('Y-m-d')) }}" required class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl"><x-input-error :messages="$errors->get('transaction_date')" class="mt-2" /></div>
                        <div><label class="block text-sm font-semibold text-slate-700 mb-2">Valor</label><div class="w-full px-4 py-3 bg-slate-100 border border-slate-200 rounded-xl text-slate-600">R$ {{ number_format($transaction->display_amount, 2, ',', '.') }}</div></div>
                        <div><label class="block text-sm font-semibold text-slate-700 mb-2">Categoria</label><select name="category_id" id="categorySelect" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl"><option value="">{{ $transaction->category_source === 'pluggy' && ! $transaction->user_modified_category ? 'Pluggy: '.$transaction->category_label : 'Sem categoria' }}</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id', $transaction->category_source === 'pluggy' && ! $transaction->user_modified_category ? null : $transaction->category_id) === (string) $category->id)>{{ $category->name }}</option>@endforeach<option value="new" @selected(old('category_id') === 'new')>+ Criar nova categoria</option></select><x-input-error :messages="$errors->get('category_id')" class="mt-2" /><label class="mt-3 flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="confirm_category" value="1" @checked(old('confirm_category'))> Fixar esta categoria somente nesta transação</label></div>
                        <div id="newCategoryField" @class(['hidden' => old('category_id') !== 'new'])><label class="block text-sm font-semibold text-slate-700 mb-2">Nova categoria</label><input name="new_category" value="{{ old('new_category') }}" class="w-full px-4 py-3 bg-blue-50 border border-blue-200 rounded-xl" placeholder="Ex.: Alimentação"><x-input-error :messages="$errors->get('new_category')" class="mt-2" /></div>
                        <div><label class="block text-sm font-semibold text-slate-700 mb-2">Forma de pagamento</label><select name="payment_method" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl">@php($selected = old('payment_method', in_array($transaction->payment_method, ['pix', 'card', 'debito', 'credito', 'dinheiro'], true) ? $transaction->payment_method : 'other'))<option value="pix" @selected($selected === 'pix')>Pix</option><option value="card" @selected($selected === 'card')>Cartão — modalidade não informada</option><option value="debito" @selected($selected === 'debito')>Cartão de débito</option><option value="credito" @selected($selected === 'credito')>Cartão de crédito</option><option value="dinheiro" @selected($selected === 'dinheiro')>Dinheiro</option><option value="other" @selected($selected === 'other')>Outro ou não identificado</option></select>@if ($transaction->user_modified_payment_method)<label class="mt-3 flex items-start gap-2 text-sm text-slate-700"><input type="checkbox" name="restore_automatic" value="1" class="mt-1 rounded border-slate-300">Voltar à classificação automática</label>@endif</div>
                        <div class="flex justify-end gap-3 pt-6 border-t border-slate-200"><a href="{{ route('pluggy.transactions.show', $transaction) }}" class="px-6 py-3 rounded-xl bg-white border border-slate-200 text-slate-700 font-medium">Cancelar</a><button class="px-6 py-3 rounded-xl {{ $isIncome ? 'bg-emerald-600' : 'bg-rose-600' }} text-white font-medium">Atualizar</button></div>
                    </form>
                @endif
            </div>
        </div>
    </div>
    <script>
        @if ($source === 'manual' && $transaction->installments_group)
        const scopeSelect = document.getElementById('installmentScope');
        const amountInput = document.querySelector('input[name="amount"]');
        const dateInput = document.querySelector('input[name="date"]');
        const scopeDefaults = {
            single: {amount: @json($transaction->amount), date: @json($transaction->date->format('Y-m-d'))},
            group: {amount: @json($groupTotal), date: @json($groupDate)}
        };
        scopeSelect.addEventListener('change', function () {
            amountInput.value = scopeDefaults[this.value].amount;
            dateInput.value = scopeDefaults[this.value].date;
        });
        @endif
        @if ($source === 'manual' && $transaction->recurring_expense_id && $recurringExpense?->is_active)
        const recurrenceScope = document.getElementById('recurrenceScope');
        const recurrenceDate = document.querySelector('input[name="date"]');
        function syncRecurrenceDate() {
            const appliesToFuture = recurrenceScope.value === 'future';
            recurrenceDate.readOnly = appliesToFuture;
            recurrenceDate.classList.toggle('bg-slate-100', appliesToFuture);
        }
        recurrenceScope.addEventListener('change', syncRecurrenceDate);
        syncRecurrenceDate();
        @endif
        const categorySelect = document.getElementById('categorySelect');
        const newCategoryField = document.getElementById('newCategoryField');
        if (categorySelect && newCategoryField) categorySelect.addEventListener('change', () => newCategoryField.classList.toggle('hidden', categorySelect.value !== 'new'));
    </script>
</x-app-layout>
