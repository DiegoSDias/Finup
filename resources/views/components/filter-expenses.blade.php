@props(['categoriesExpense'])

{{-- DESPESAS - Formulário de Filtros --}}
<form method="GET" class="finup-movement-filters finup-movement-filters--expense p-6 bg-gradient-to-r from-slate-50 to-slate-100/50 border-b border-slate-200">
    <div class="finup-filter-fields">

        {{-- Categoria --}}
        <div>
            <label for="expense-filter-category" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Categoria</label>
            <select id="expense-filter-category" name="expense_category"
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm bg-white focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all shadow-sm hover:border-slate-300">
                <option value="">Todas as categorias</option>
                @foreach ($categoriesExpense as $expense)
                    <option value="{{ $expense->id }}" @selected(request('expense_category') == $expense->id)>
                        {{ $expense->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Método de Pagamento --}}
        <div>
            <label for="expense-filter-payment" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Pagamento</label>
            <select id="expense-filter-payment" name="payment_mode"
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm bg-white focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all shadow-sm hover:border-slate-300">
                <option value="">Todos</option>
                <option value="cartao" @selected(request('payment_mode') === 'cartao')>Cartão — modalidade não informada</option>
                <option value="pix" @selected(request('payment_mode') === 'pix')>Pix</option>
                <option value="dinheiro" @selected(request('payment_mode') === 'dinheiro')>Dinheiro</option>
                <option value="debito" @selected(request('payment_mode') === 'debito')>Cartão de débito</option>
                <option value="credito" @selected(request('payment_mode') === 'credito')>Cartão de crédito</option>
            </select>
        </div>

        {{-- Parcelado --}}
        <div>
            <label for="expense-filter-installment" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Parcelado</label>
            <select id="expense-filter-installment" name="is_installment"
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm bg-white focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all shadow-sm hover:border-slate-300">
                <option value="">Todos</option>
                <option value="1" @selected(request('is_installment') === '1')>Sim</option>
                <option value="0" @selected(request('is_installment') === '0')>Não</option>
            </select>
        </div>

        {{-- Data --}}
        <div>
            <label for="expense-filter-date" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Data</label>
            <input id="expense-filter-date" type="date" name="date_value" value="{{ request('date_value') }}"
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all shadow-sm hover:border-slate-300">
        </div>

        {{-- Filtro de Valor --}}
        <div>
            <label for="expense-filter-comparison" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Comparação</label>
            <select id="expense-filter-comparison" name="expense_value_op"
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm bg-white focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all shadow-sm hover:border-slate-300">
                <option value=">=" @selected(request('expense_value_op') === '>=' || !request('expense_value_op'))>Maior ou igual</option>
                <option value="<=" @selected(request('expense_value_op') === '<=')>Menor ou igual</option>
                <option value="=" @selected(request('expense_value_op') === '=')>Igual a</option>
            </select>
        </div>

        <div>
            <label for="expense-filter-value" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Valor</label>
            <input id="expense-filter-value" type="number" step="0.01" name="expense_value" value="{{ request('expense_value') }}"
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm bg-white focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all shadow-sm hover:border-slate-300"
                placeholder="R$ 0,00">
        </div>

        {{-- Botões --}}
        <div class="finup-filter-actions">
            <button type="submit"
                class="group px-5 py-2.5 bg-gradient-to-r from-slate-700 to-slate-600 text-white rounded-xl text-sm font-medium hover:from-slate-800 hover:to-slate-700 transition-all duration-200 shadow-sm hover:shadow-md flex items-center gap-2">
                <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filtrar
            </button>

            @if(request()->hasAny(['expense_category', 'payment_mode', 'is_installment', 'expense_value_op', 'expense_value']))
                <a href="{{ url()->current() }}"
                    class="group px-5 py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-medium hover:bg-slate-50 hover:border-slate-300 transition-all duration-200 shadow-sm hover:shadow-md flex items-center gap-2">
                    <svg class="w-4 h-4 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Limpar
                </a>
            @endif
        </div>
    </div>
</form>
