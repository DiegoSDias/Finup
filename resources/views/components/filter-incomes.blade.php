@props(['categoriesIncome'])

{{-- RECEITAS - Formulário de Filtros --}}
<form method="GET" class="finup-movement-filters finup-movement-filters--income p-6 bg-gradient-to-r from-slate-50 to-slate-100/50 border-b border-slate-200">
    <div class="finup-filter-fields">

        {{-- Categoria --}}
        <div>
            <label for="income-filter-category" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Categoria</label>
            <select id="income-filter-category" name="income_category"
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all shadow-sm hover:border-slate-300">
                <option value="">Todas as categorias</option>
                @foreach ($categoriesIncome as $income)
                    <option value="{{ $income->id }}" @selected(request('income_category') == $income->id)>
                        {{ $income->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Data --}}
        <div>
            <label for="income-filter-date" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Data</label>
            <input id="income-filter-date" type="date" name="date_value" value="{{ request('date_value') }}"
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all shadow-sm hover:border-slate-300">
        </div>

        {{-- Filtro de Valor --}}
        <div>
            <label for="income-filter-comparison" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Comparação</label>
            <select id="income-filter-comparison" name="income_value_op"
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all shadow-sm hover:border-slate-300">
                <option value=">=" @selected(request('income_value_op') === '>=' || !request('income_value_op'))>Maior ou igual</option>
                <option value="<=" @selected(request('income_value_op') === '<=')>Menor ou igual</option>
                <option value="=" @selected(request('income_value_op') === '=')>Igual a</option>
            </select>
        </div>

        <div>
            <label for="income-filter-value" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Valor</label>
            <input id="income-filter-value" type="number" step="0.01" name="income_value" value="{{ request('income_value') }}"
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all shadow-sm hover:border-slate-300"
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

            @if(request()->hasAny(['income_category', 'income_value_op', 'income_value']))
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
