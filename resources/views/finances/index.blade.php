<x-app-layout>
    <div class="finup-finances finup-finances-page">
        <x-finup-back :href="route('dashboard')" />
        <div class="finup-finance-heading">
            <div>
                <h1 class="text-3xl font-semibold text-white">Visão <span class="text-green-500">Financeira</span></h1>
                <p class="mt-3 text-sm text-gray-300">Acompanhe como estão as suas finanças. Selecione o ano desejado.</p>
                <a href="{{ route('analyses.index', ['year' => $anoSelecionado]) }}" class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-green-300 underline underline-offset-4 hover:text-green-200">Ver análises e gráficos <span aria-hidden="true">→</span></a>
            </div>
            @php
                $anoAtual = $anoSelecionado;
                $anoAnterior = $anoAtual - 1;
                $anoProximo = $anoAtual + 1;
            @endphp
            <div class="finup-year-switcher flex items-center gap-3 text-white">
                @if ($anoAtual > 1900)
                    <a href="{{ route('finances.index', ['year' => $anoAnterior]) }}" aria-label="Ano anterior" class="rounded-lg border border-neutral-600 px-3 py-2 hover:bg-neutral-800">←</a>
                @endif
                <details class="relative">
                    <summary class="cursor-pointer list-none rounded-lg border border-neutral-600 px-5 py-2 text-center"><span class="sr-only">Ano selecionado: </span>{{ $anoAtual }} <span aria-hidden="true" class="text-green-400">⌄</span></summary>
                    <div class="absolute right-0 z-20 mt-2 max-h-64 min-w-full overflow-y-auto rounded-lg border border-neutral-600 bg-neutral-900 py-1 shadow-xl">
                        @foreach (range(min(2100, $anoSelecionado + 3), max(1900, $anoSelecionado - 3)) as $ano)
                            <a href="{{ route('finances.index', ['year' => $ano]) }}" class="block px-5 py-2 hover:bg-neutral-800 {{ $ano == $anoAtual ? 'text-green-400' : 'text-white' }}">{{ $ano }}</a>
                        @endforeach
                    </div>
                </details>
                @if ($anoAtual < 2100)
                    <a href="{{ route('finances.index', ['year' => $anoProximo]) }}" aria-label="Próximo ano" class="rounded-lg border border-neutral-600 px-3 py-2 hover:bg-neutral-800">→</a>
                @endif
            </div>
        </div>

        <div class="finup-annual-cards mt-10 grid gap-5 md:grid-cols-3">
            <div class="finup-annual-card overflow-hidden">
                <h3 class="bg-green-700 px-5 py-3 text-sm font-medium uppercase text-white">Total anual de gastos</h3>
                <div class="finup-annual-value"><svg fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v18m4-14c-1-2-8-2-8 1s8 3 8 6-7 4-9 1M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0" /></svg><div><p class="text-3xl font-semibold text-white">R$ {{ number_format($data['totalExpense'] ?? 0, 2, ',', '.') }}</p><p class="mt-1 text-sm text-gray-400">Acumulado em {{ $anoSelecionado }}</p></div></div>
            </div>
            <div class="finup-annual-card overflow-hidden">
                <h3 class="bg-green-700 px-5 py-3 text-sm font-medium uppercase text-white">Média mensal</h3>
                <div class="finup-annual-value"><svg fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 16h3v6H4zM10 10h3v12h-3zM16 3h3v19h-3z" /></svg><div><p class="text-3xl font-semibold text-white">R$ {{ number_format($data['mediaAnual'] ?? 0, 2, ',', '.') }}</p><p class="mt-1 text-sm text-gray-400">Por mês em {{ $anoSelecionado }}</p></div></div>
            </div>
            <div class="finup-annual-card overflow-hidden">
                <h3 class="bg-green-700 px-5 py-3 text-sm font-medium uppercase text-white">Maior gasto</h3>
                <div class="finup-annual-value"><svg fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24" aria-hidden="true"><path d="m2 18 6-9 5 3 5-7 4 4M2 22h20" /></svg><div><p class="text-3xl font-semibold text-white">@if (isset($data['maiorGasto']) && $data['maiorGasto'] > 0) R$ {{ number_format($data['maiorGasto'], 2, ',', '.') }} @else — @endif</p><p class="mt-1 text-sm text-gray-400">Pico em um mês</p></div></div>
            </div>
        </div>

        <div class="mt-14" x-data="{ selected: 1 }">
            <h3 class="finup-months-title font-semibold text-white">Meses</h3>
            <p class="mt-2 text-sm text-gray-300">Selecione o mês para ver o resumo.</p>
            <div class="mt-6 overflow-x-auto pb-3">
                <div class="finup-month-timeline min-w-max">
                    @foreach ($data['meses'] as $mes)
                        <button type="button" @click="selected = {{ (int) $mes['numero'] }}" :aria-pressed="selected === {{ (int) $mes['numero'] }} ? 'true' : 'false'" :class="selected === {{ (int) $mes['numero'] }} ? 'text-green-400 underline' : 'text-gray-200'" class="rounded px-1 py-2 text-sm uppercase hover:text-green-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-green-400">{{ $mes['nome'] }}</button>
                    @endforeach
                </div>
            </div>
            <div class="finup-month-summary-layout mt-8 grid gap-8 lg:grid-cols-[16rem_1fr]">
                <div>
                    @foreach ($data['meses'] as $mes)
                        <div x-show="selected === {{ (int) $mes['numero'] }}" @if ($loop->index > 0) style="display:none" @endif>
                            <h4 class="text-xl font-semibold text-green-400">{{ $mes['nome'] }} {{ $anoSelecionado }}</h4>
                            <p class="mt-5 text-gray-200">Entradas: R$ {{ number_format($data['finalIncomeBalance'][$mes['numero']] ?? 0, 2, ',', '.') }}</p>
                            <p class="mt-3 text-gray-200">Despesas: R$ {{ number_format($data['expenseBalance'][$mes['numero']] ?? 0, 2, ',', '.') }}</p>
                            <a href="{{ route('month.show', ['year' => $anoSelecionado, 'month' => $mes['numero']]) }}" class="mt-6 inline-flex rounded-lg bg-green-200 px-4 py-2 text-sm font-medium text-neutral-950 hover:bg-green-300">Ver mais detalhes →</a>
                        </div>
                    @endforeach
                </div>
                <x-financial-chart :data="$data" :year="$anoSelecionado" />
            </div>
        </div>
    </div>
</x-app-layout>
