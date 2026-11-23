@props(['filters', 'options'])
@php($month = $filters['month'] ?? null)
@if ($errors->any())
        <div class="finup-analysis-errors" role="alert">
            <p>Confira os filtros selecionados:</p>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="GET" action="{{ route('analyses.index') }}" class="finup-analysis-filters"
        aria-label="Filtros de todos os gráficos"
        x-data="{ group: @js((string) ($filters['group_id'] ?? '')), category: @js((string) ($filters['category_id'] ?? '')), source: @js($filters['source'] ?? ''), bank: @js($filters['bank'] ?? '') }">
        <div class="finup-analysis-filter-grid">
            <div>
                <label for="analysis-year">Ano</label>
                <input id="analysis-year" name="year" type="number" min="1900" max="2100" value="{{ $filters['year'] }}" required>
            </div>
            <div>
                <label for="analysis-month">Mês</label>
                <select id="analysis-month" name="month">
                    <option value="">Ano inteiro</option>
                    @foreach (\App\Support\Months::MONTHS as $option)
                        <option value="{{ $option['numero'] }}" @selected((string) $month === (string) $option['numero'])>{{ $option['nome'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="analysis-group">Grupo</label>
                <select id="analysis-group" name="group_id" x-model="group" @change="category = ''">
                    <option value="">Todos os grupos</option>
                    <option value="none" @selected(($filters['group_id'] ?? '') === 'none')>Sem grupo</option>
                    @foreach ($options['groups'] as $group)
                        <option value="{{ $group->id }}" @selected((string) ($filters['group_id'] ?? '') === (string) $group->id)>{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="analysis-category">Categoria</label>
                <select id="analysis-category" name="category_id" x-model="category">
                    <option value="">Todas as categorias</option>
                    <option value="none" @selected(($filters['category_id'] ?? '') === 'none') :disabled="group !== '' && group !== 'none'" x-show="group === '' || group === 'none'">Sem categoria</option>
                    @foreach ($options['categories'] as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)
                            :disabled="group !== '' && group !== @js((string) ($category->group_id ?? 'none'))"
                            x-show="group === '' || group === @js((string) ($category->group_id ?? 'none'))">{{ $category->name }} · {{ $category->type === 'income' ? 'Receita' : 'Despesa' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="analysis-source">Origem</label>
                <select id="analysis-source" name="source" x-model="source" @change="bank = ''">
                    <option value="">Todas as origens</option>
                    <option value="manual" @selected(($filters['source'] ?? '') === 'manual')>Lançamentos manuais</option>
                    <option value="imported" @selected(($filters['source'] ?? '') === 'imported')>Lançamentos importados</option>
                </select>
            </div>
            <div>
                <label for="analysis-bank">Banco ou conta</label>
                <select id="analysis-bank" name="bank" x-model="bank">
                    <option value="">Todos os bancos e contas</option>
                    <optgroup label="Lançamentos manuais" x-show="source !== 'imported'">
                        <option value="none" @selected(($filters['bank'] ?? '') === 'none') :disabled="source === 'imported'">Sem uso de banco</option>
                        <option value="unknown" @selected(($filters['bank'] ?? '') === 'unknown') :disabled="source === 'imported'">Banco não informado</option>
                        @foreach ($options['manualBanks'] as $bank)
                            <option value="manual:{{ $bank->id }}" @selected(($filters['bank'] ?? '') === 'manual:'.$bank->id) :disabled="source === 'imported'">{{ $bank->name }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Contas importadas" x-show="source !== 'manual'">
                        @foreach ($options['accounts'] as $account)
                            <option value="account:{{ $account->id }}" @selected(($filters['bank'] ?? '') === 'account:'.$account->id) :disabled="source === 'manual'">{{ $account->connection?->display_label ?? 'Instituição não informada' }} · {{ $account->name }}@if ($account->number_last_four) · {{ $account->number_last_four }}@endif</option>
                        @endforeach
                    </optgroup>
                </select>
            </div>
        </div>
        <div class="finup-analysis-filter-footer">
            <p>Os filtros atualizam todos os gráficos e os totais desta página.</p>
            <div class="finup-analysis-actions">
                <a href="{{ route('analyses.index', ['year' => $filters['year']]) }}">Limpar filtros</a>
                <button type="submit">Aplicar filtros <span aria-hidden="true">→</span></button>
            </div>
        </div>
    </form>
