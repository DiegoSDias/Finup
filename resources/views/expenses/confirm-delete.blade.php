<x-app-layout>
    <div class="min-h-screen bg-slate-50 py-8">
        <div class="max-w-xl mx-auto px-4">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8">
                <h1 class="text-2xl font-semibold text-slate-900">Confirmar exclusão</h1>
                <p class="mt-3 text-slate-600">
                    @if ($expense->recurring_expense_id)
                        Escolha entre remover somente esta ocorrência ou encerrar a recorrência a partir dela.
                    @elseif ($expense->installments_group)
                        Escolha se deseja excluir somente esta parcela ou todas as parcelas do grupo.
                    @else
                        Esta ação não pode ser desfeita.
                    @endif
                </p>
                <p class="mt-4 text-slate-700">
                    {{ $expense->description ?: 'Lançamento sem descrição' }} —
                    {{ $expense->date->format('d/m/Y') }} — R$ {{ number_format((float) $expense->amount, 2, ',', '.') }}
                </p>
                <form action="{{ route('expenses.destroy', $expense) }}" method="POST" class="mt-6 space-y-4">
                    @csrf
                    @method('DELETE')
                    @if ($expense->recurring_expense_id)
                        <label class="block"><input type="radio" name="recurrence_scope" value="single" checked> Somente esta ocorrência</label>
                        <label class="block"><input type="radio" name="recurrence_scope" value="future"> Esta e as próximas — encerrar a recorrência e remover as ocorrências futuras</label>
                    @elseif ($expense->installments_group)
                        <label class="block"><input type="radio" name="installment_scope" value="single" checked> Somente esta parcela</label>
                        <label class="block"><input type="radio" name="installment_scope" value="group"> Todo o grupo de parcelas</label>
                    @endif
                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="px-4 py-2 bg-red-700 text-white rounded-lg">Confirmar exclusão</button>
                        <a href="{{ route('month.show', [$expense->year, $expense->month]) }}" class="px-4 py-2 border rounded-lg">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
