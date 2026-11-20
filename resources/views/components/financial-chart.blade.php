@props(['data', 'year'])
@php
    // Only scale the existing monthly totals into SVG coordinates.
    $chartIncome = array_map(fn ($month) => (float) ($data['finalIncomeBalance'][$month] ?? 0), range(1, 12));
    $chartExpenses = array_map(fn ($month) => (float) ($data['expenseBalance'][$month] ?? 0), range(1, 12));
    $chartMin = min(0, ...$chartIncome, ...$chartExpenses);
    $chartMax = max(0, ...$chartIncome, ...$chartExpenses);
    $chartRange = max(1, $chartMax - $chartMin);
    $chartPoints = fn ($values) => implode(' ', array_map(fn ($index) =>
        number_format(55 + $index * 65, 2, '.', '').','.number_format(260 - (($values[$index] - $chartMin) / $chartRange * 230), 2, '.', ''), range(0, 11)));
@endphp
<figure class="finup-finance-chart" aria-label="Entradas e despesas por mês em {{ $year }}">
    <svg viewBox="0 0 800 300" role="img" aria-labelledby="annual-chart-title annual-chart-description">
        <title id="annual-chart-title">Entradas e despesas em {{ $year }}</title>
        <desc id="annual-chart-description">Comparação dos totais mensais já apresentados nos resumos. A linha verde mostra as entradas e a linha verde clara mostra as despesas.</desc>
        @foreach (range(0, 4) as $line)
            <line x1="55" y1="{{ 30 + $line * 57.5 }}" x2="770" y2="{{ 30 + $line * 57.5 }}" stroke="#ffffff18" />
            @if ($chartRange > 1)
                <text x="45" y="{{ 34 + $line * 57.5 }}" text-anchor="end" fill="#bbb" font-size="11">{{ number_format($chartMax - $line * ($chartMax - $chartMin) / 4, 0, ',', '.') }}</text>
            @endif
        @endforeach
        <path d="M55 20v240h715" fill="none" stroke="#ccc" stroke-width="1.5" />
        <polyline points="{{ $chartPoints($chartIncome) }}" fill="none" stroke="#27ae60" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        <polyline points="{{ $chartPoints($chartExpenses) }}" fill="none" stroke="#93e2ad" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
        @foreach ($data['meses'] as $chartMonth)
            <text x="{{ 55 + ($loop->index * 65) }}" y="285" text-anchor="middle" fill="#ddd" font-size="11">{{ mb_substr($chartMonth['nome'], 0, 3) }}</text>
        @endforeach
    </svg>
    <figcaption><span>Entradas</span><span>Despesas</span></figcaption>
    @if ($chartMax == 0 && $chartMin == 0)<p class="finup-chart-empty">Sem movimentações em {{ $year }}. Os valores aparecerão conforme seus lançamentos.</p>@endif
</figure>
