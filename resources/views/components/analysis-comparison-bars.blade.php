@props(['rows', 'unit' => 'itens'])
@php
    $money = fn ($cents) => 'R$ '.number_format($cents / 100, 2, ',', '.');
    $max = max([1, ...array_map(fn ($row) => max(abs($row['income']), abs($row['expense'])), $rows)]);
@endphp
@if (count($rows) > 0)
    <div class="finup-analysis-legend"><span class="finup-analysis-legend-income">Receitas</span><span class="finup-analysis-legend-expense">Despesas líquidas</span></div>
    <p class="finup-analysis-list-count">{{ count($rows) }} {{ $unit }} no período.@if (count($rows) > 4) Role a lista para ver todos.@endif</p>
    <ul class="finup-analysis-comparison-list" tabindex="0" aria-label="Comparação de receitas e despesas">
        @foreach ($rows as $row)
            <li data-analysis-key="{{ $row['key'] }}">
                <div class="finup-analysis-row-label">{{ $row['label'] }}@if (isset($row['category_type']))<small>{{ $row['category_type'] === 'income' ? 'Receita' : 'Despesa' }}</small>@endif</div>
                @foreach (['income' => 'Receitas', 'expense' => 'Despesas'] as $type => $label)
                    <div class="finup-analysis-paired-bar">
                        <span class="sr-only">{{ $label }}: </span>
                        <div class="finup-analysis-track" aria-hidden="true"><span class="finup-analysis-bar finup-analysis-bar--{{ $type }} {{ $row[$type] < 0 ? 'finup-analysis-bar--credit' : '' }}" style="width: {{ number_format(abs($row[$type]) * 100 / $max, 4, '.', '') }}%"></span></div>
                        <span class="finup-analysis-{{ $type }}">{{ $money($row[$type]) }}</span>
                    </div>
                @endforeach
            </li>
        @endforeach
    </ul>
    <p class="finup-analysis-chart-note">Estornos e cashback reduzem as despesas. Valores negativos representam créditos líquidos.</p>
@else <x-analysis-empty /> @endif
