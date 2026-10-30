<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
        <div class="mx-auto max-w-4xl px-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium uppercase tracking-widest text-rose-600">Planejamento</p>
                    <h1 class="mt-1 text-3xl font-light text-slate-900">Despesas recorrentes</h1>
                    <p class="mt-2 text-sm text-slate-500">Uma recorrência gera ocorrências independentes; ela não é um parcelamento.</p>
                </div>
                <a href="{{ route('recurring-expenses.create') }}" class="rounded-xl bg-rose-600 px-5 py-3 font-medium text-white shadow-sm hover:bg-rose-700">Nova recorrência</a>
            </div>

            <div class="mt-8 space-y-4">
                @forelse ($recurrences as $recurrence)
                    @php($isCompleted = $recurrence->canBeDeleted())
                    <article class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-lg font-semibold text-slate-900">{{ $recurrence->description ?: 'Despesa sem descrição' }}</h2>
                                    <span @class(['rounded-full px-2.5 py-1 text-xs font-medium', 'bg-emerald-100 text-emerald-700' => $recurrence->is_active && ! $isCompleted, 'bg-slate-100 text-slate-600' => ! $recurrence->is_active || $isCompleted])>{{ $isCompleted ? 'Concluída' : ($recurrence->is_active ? 'Ativa' : 'Encerrada') }}</span>
                                </div>
                                <p class="mt-2 text-sm text-slate-600">
                                    R$ {{ number_format((float) $recurrence->amount, 2, ',', '.') }} ·
                                    {{ ['monthly' => 'Mensal', 'quarterly' => 'Trimestral', 'semiannual' => 'Semestral', 'annual' => 'Anual'][$recurrence->frequency] }} ·
                                    {{ $recurrence->category?->name ?? 'Categoria indisponível' }}
                                </p>
                                <p class="mt-1 text-sm text-slate-500">Início: {{ $recurrence->start_date->format('d/m/Y') }}@if($recurrence->end_date) · Término: {{ $recurrence->end_date->format('d/m/Y') }}@endif · {{ $recurrence->manualBank?->name ?? ($recurrence->bank_status === 'none' ? 'Sem banco' : 'Banco não informado') }} · {{ $recurrence->payment_mode === 'credito' ? 'Cartão de crédito' : ($recurrence->payment_mode === 'debito' ? 'Cartão de débito' : ucfirst($recurrence->payment_mode)) }}</p>
                            </div>
                            <div class="flex items-center gap-3 text-sm font-medium">
                                <a href="{{ route('month.show', [$recurrence->start_date->year, $recurrence->start_date->month]) }}" class="text-blue-700 hover:text-blue-900">Ver ocorrências</a>
                                @if ($recurrence->is_active && ! $isCompleted)
                                    <a href="{{ route('recurring-expenses.edit', $recurrence) }}" class="text-amber-700 hover:text-amber-900">Editar série</a>
                                @endif
                            </div>
                        </div>

                        @if ($recurrence->is_active && ! $isCompleted)
                            <form action="{{ route('recurring-expenses.end', $recurrence) }}" method="POST" class="mt-5 flex flex-wrap items-end gap-3 border-t border-slate-100 pt-4">
                                @csrf
                                <label class="text-sm text-slate-700">Encerrar a partir de
                                    <input type="date" name="from_date" value="{{ now()->toDateString() }}" class="mt-1 rounded-lg border-slate-200">
                                </label>
                                <label class="text-sm text-slate-700"><input type="checkbox" name="remove_future" value="1" checked> Remover ocorrências futuras já geradas</label>
                                <button type="submit" class="rounded-lg border border-rose-200 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">Encerrar</button>
                            </form>
                        @endif

                        @if ($recurrence->canBeDeleted())
                            <form action="{{ route('recurring-expenses.destroy', $recurrence) }}" method="POST" class="mt-4 border-t border-slate-100 pt-4" onsubmit="return confirm('Excluir a recorrência? As despesas já registradas serão preservadas no histórico.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Excluir recorrência concluída</button>
                            </form>
                        @endif
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">Nenhuma despesa recorrente cadastrada.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
