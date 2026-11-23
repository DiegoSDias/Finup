<x-app-layout>
    @php($money = fn ($cents) => 'R$ '.number_format($cents / 100, 2, ',', '.'))
    <div class="finup-analyses">
        <x-finup-back :href="route('finances.index', ['year' => $filters['year']])" />
        <header class="finup-analysis-heading">
            <div>
                <p class="finup-analysis-eyebrow">Seu dinheiro em perspectiva</p>
                <h1>Análises <span>financeiras</span></h1>
                <p>Compare suas movimentações e descubra o que mais pesa nas suas finanças.</p>
            </div>
            <div class="finup-analysis-period">
                @if ($filters['year'] > 1900)<a href="{{ route('analyses.index', array_replace($filters, ['year' => $filters['year'] - 1])) }}" aria-label="Ano anterior">←</a>@endif
                <span>{{ $analysis['period_label'] }}</span>
                @if ($filters['year'] < 2100)<a href="{{ route('analyses.index', array_replace($filters, ['year' => $filters['year'] + 1])) }}" aria-label="Próximo ano">→</a>@endif
            </div>
        </header>

        <x-analysis-filters :filters="$filters" :options="$options" />

        <div class="finup-analysis-totals" aria-label="Resumo do período filtrado">
            <div><span>Receitas do período</span><strong class="finup-analysis-income">{{ $money($analysis['totals']['income']) }}</strong><small>Entradas no período selecionado</small></div>
            <div><span>Despesas líquidas</span><strong class="finup-analysis-expense">{{ $money($analysis['totals']['expense']) }}</strong><small>Gastos com estornos e cashback descontados</small></div>
            <div><span>Saldo do período</span><strong class="{{ $analysis['totals']['balance'] < 0 ? 'finup-analysis-expense' : 'finup-analysis-income' }}">{{ $money($analysis['totals']['balance']) }}</strong><small>Receitas menos despesas dos filtros selecionados</small></div>
        </div>

        <div class="finup-analysis-grid">
            <x-analysis-card id="receitas-despesas" title="Receitas × despesas" number="01" description="Compare entradas e saídas ao longo do período.">
                @if ($analysis['has_movements'])
                    <x-analysis-timeline-chart id="income-expense-chart" :rows="$analysis['periods']" mode="comparison" />
                @else <x-analysis-empty /> @endif
            </x-analysis-card>

            <x-analysis-card id="grupos" title="Receitas e despesas por grupo" number="02" description="Veja os totais das categorias reunidas em cada grupo.">
                <x-analysis-comparison-bars :rows="$analysis['groups']" unit="grupos" />
            </x-analysis-card>

            <x-analysis-card id="categorias" title="Receitas e despesas por categoria" number="03" description="Entenda a participação de cada categoria nas suas finanças.">
                <x-analysis-comparison-bars :rows="$analysis['categories']" unit="categorias" />
            </x-analysis-card>

            <x-analysis-card id="saldo" title="Evolução do saldo" number="04" description="Resultado acumulado dentro do período, partindo de zero.">
                @if ($analysis['has_movements'])
                    <x-analysis-timeline-chart id="balance-chart" :rows="$analysis['periods']" mode="balance" />
                @else <x-analysis-empty /> @endif
            </x-analysis-card>

            <x-analysis-card id="maiores-despesas" title="Maiores despesas" number="05" description="Os dez maiores lançamentos de despesa do período.">
                @if (count($analysis['largest']) > 0)
                    @if (count($analysis['largest']) > 5)<p class="finup-analysis-list-count">Role a lista para ver as {{ count($analysis['largest']) }} maiores despesas.</p>@endif
                    <ol class="finup-analysis-ranking">
                        @foreach ($analysis['largest'] as $movement)
                            <li>
                                <div class="finup-analysis-ranking-heading">
                                    <span class="finup-analysis-rank">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    <div>
                                        <a href="{{ $movement['source'] === 'manual' ? route('expenses.show', $movement['id']) : route('pluggy.transactions.show', $movement['id']) }}">{{ $movement['description'] }}</a>
                                        <small>{{ \Carbon\Carbon::parse($movement['date'])->format('d/m/Y') }} · {{ $movement['source'] === 'manual' ? 'Manual' : 'Importado' }}</small>
                                    </div>
                                    <strong>{{ $money($movement['cents']) }}</strong>
                                </div>
                                <div class="finup-analysis-track" aria-hidden="true"><span class="finup-analysis-bar finup-analysis-bar--expense" style="width: {{ number_format($movement['cents'] * 100 / $analysis['largest'][0]['cents'], 4, '.', '') }}%"></span></div>
                            </li>
                        @endforeach
                    </ol>
                @else <x-analysis-empty title="Nenhuma despesa encontrada" /> @endif
            </x-analysis-card>

            <x-analysis-card id="formas-de-pagamento" title="Despesas por forma de pagamento" number="06" description="Descubra quais formas de pagamento concentram seus gastos.">
                <x-payment-method-chart :summary="$analysis['payments']" />
            </x-analysis-card>
        </div>
    </div>
</x-app-layout>
