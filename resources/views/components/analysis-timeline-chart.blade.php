@props(['id', 'rows', 'mode'])
@php
    $money = fn ($cents) => 'R$ '.number_format($cents / 100, 2, ',', '.');
    $axis = fn ($cents) => abs($cents) >= 100000000 ? 'R$ '.number_format($cents / 100000000, 1, ',', '.').' mi' : (abs($cents) >= 100000 ? 'R$ '.number_format($cents / 100000, 1, ',', '.').' mil' : $money($cents));
    $values = $mode === 'balance' ? array_column($rows, 'balance') : array_merge(array_column($rows, 'income'), array_column($rows, 'expense'));
    $min = min(0, ...$values);
    $max = max(0, ...$values);
    if ($min === $max) $max = $min + 100;
    $range = $max - $min;
    $y = fn ($value) => 240 - (($value - $min) / $range * 210);
    $step = 560 / count($rows);
    $x = fn ($index) => 95 + ($index + .5) * $step;
    $points = implode(' ', array_map(fn ($index) => number_format($x($index), 2, '.', '').','.number_format($y($rows[$index]['balance']), 2, '.', ''), array_keys($rows)));
@endphp
<div class="finup-analysis-legend">
    @if ($mode === 'comparison')<span class="finup-analysis-legend-income">Receitas</span><span class="finup-analysis-legend-expense">Despesas líquidas</span>
    @else <span class="finup-analysis-legend-income">Saldo acumulado no período</span> @endif
</div>
<figure>
    <div class="finup-analysis-svg-scroll" tabindex="0" aria-label="Gráfico por {{ count($rows) > 12 ? 'dia' : 'mês' }}; valores disponíveis abaixo">
        <svg class="finup-analysis-svg" viewBox="0 0 680 285" role="img" aria-labelledby="{{ $id }}-title {{ $id }}-description">
            <title id="{{ $id }}-title">{{ $mode === 'comparison' ? 'Receitas e despesas no período' : 'Evolução do saldo acumulado no período' }}</title>
            <desc id="{{ $id }}-description">{{ $mode === 'comparison' ? 'Barras verdes mostram receitas e barras vermelhas mostram despesas líquidas.' : 'A linha mostra o acumulado de receitas menos despesas, começando em zero no início do período.' }} Consulte os valores exatos em Ver valores.</desc>
            @foreach (range(0, 4) as $tick)
                @php($tickValue = $max - $range * $tick / 4)
                <line x1="95" y1="{{ 30 + $tick * 52.5 }}" x2="655" y2="{{ 30 + $tick * 52.5 }}" stroke="#ffffff12" />
                <text x="85" y="{{ 34 + $tick * 52.5 }}" text-anchor="end" fill="#aab7ae" font-size="14">{{ $axis($tickValue) }}</text>
            @endforeach
            <line x1="95" y1="{{ $y(0) }}" x2="655" y2="{{ $y(0) }}" stroke="#b5c6b966" />
            @if ($mode === 'balance')<polyline points="{{ $points }}" fill="none" stroke="var(--finup-chart-income)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />@endif
            @foreach ($rows as $index => $row)
                @if ($mode === 'comparison')
                    @foreach (['income' => 'var(--finup-chart-income)', 'expense' => 'var(--finup-chart-expense)'] as $type => $color)
                        <rect x="{{ $x($index) + ($type === 'income' ? -$step * .29 : $step * .03) }}" y="{{ min($y(0), $y($row[$type])) }}" width="{{ $step * .26 }}" height="{{ abs($y($row[$type]) - $y(0)) }}" rx="2" fill="{{ $row[$type] < 0 ? 'var(--finup-chart-income)' : $color }}"><title>{{ $row['full_label'] }} · {{ $type === 'income' ? 'Receitas' : 'Despesas' }}: {{ $money($row[$type]) }}</title></rect>
                    @endforeach
                @else
                    <circle cx="{{ $x($index) }}" cy="{{ $y($row['balance']) }}" r="3.5" fill="{{ $row['balance'] < 0 ? 'var(--finup-chart-expense)' : 'var(--finup-chart-income)' }}"><title>{{ $row['full_label'] }}: {{ $money($row['balance']) }}</title></circle>
                @endif
                @if (count($rows) <= 12 || $index % 3 === 0 || $loop->last)
                    <text x="{{ $x($index) }}" y="267" text-anchor="middle" fill="#c1cec5" font-size="14">{{ $row['label'] }}</text>
                @endif
            @endforeach
        </svg>
    </div>
    <p class="finup-analysis-scroll-hint">Deslize o gráfico para consultar todo o período.</p>
    <details class="finup-analysis-data">
        <summary>Ver valores</summary>
        <div class="finup-analysis-table-scroll"><table>
            <caption class="sr-only">Valores do gráfico</caption>
            <thead><tr><th>Período</th>@if ($mode === 'comparison')<th>Receitas</th><th>Despesas líquidas</th>@else<th>Saldo acumulado</th>@endif</tr></thead>
            <tbody>@foreach ($rows as $row)<tr><th>{{ $row['full_label'] }}</th>@if ($mode === 'comparison')<td>{{ $money($row['income']) }}</td><td>{{ $money($row['expense']) }}</td>@else<td>{{ $money($row['balance']) }}</td>@endif</tr>@endforeach</tbody>
        </table></div>
    </details>
</figure>
