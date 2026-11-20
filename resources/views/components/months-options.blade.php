@props(['year', 'month'])
@php
    $current = \Carbon\Carbon::create($year, $month, 1);
    $prev = $current->copy()->subMonth();
    $next = $current->copy()->addMonth();

    $meses = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
    ];
@endphp

<div class="finup-month-navigation">
    {{-- Informações do Mês --}}
    <div>
        <h1 class="text-3xl font-light text-slate-900 mb-1">
            {{ $meses[(int) $month] }} <span>{{ $year }}</span>
        </h1>
        <p class="text-slate-500 text-sm font-light">Veja o seu resumo financeiro mensal.</p>
    </div>

    {{-- Navegação --}}
    <div class="flex items-center gap-2 ml-auto">
        <a href="{{ route('month.show', [$prev->year, $prev->month]) }}"
           class="group flex items-center gap-2 px-4 py-2.5 bg-white rounded-xl border border-slate-200 hover:bg-slate-50 hover:border-slate-300 transition-all duration-200 shadow-sm hover:shadow-md">
            <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-700 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            <span class="text-sm font-medium text-slate-600 group-hover:text-slate-900 transition-colors">
                {{ $prev->format('m/Y') }}
            </span>
        </a>

        <a href="{{ route('month.show', [$next->year, $next->month]) }}"
           class="group flex items-center gap-2 px-4 py-2.5 bg-white rounded-xl border border-slate-200 hover:bg-slate-50 hover:border-slate-300 transition-all duration-200 shadow-sm hover:shadow-md">
            <span class="text-sm font-medium text-slate-600 group-hover:text-slate-900 transition-colors">
                {{ $next->format('m/Y') }}
            </span>
            <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-700 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div>
