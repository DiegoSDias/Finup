<x-app-layout>
    @php
        $isIncome = $type === 'income';
        $isPluggy = $source === 'pluggy';
        $date = $isPluggy ? $transaction->transaction_date : $transaction->date;
        $amount = $isPluggy ? $transaction->display_amount : $transaction->amount;
    @endphp
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
        <div class="mx-auto max-w-3xl px-4">
            @if(session('success'))<div class="mb-4 rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>@endif
            @if($errors->any())<div role="alert" class="mb-4 rounded-xl bg-red-50 p-4 text-red-800">{{ $errors->first() }}</div>@endif
            <a href="{{ route('month.show', [$date->year, $date->month]) }}" class="mb-5 inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900">← Voltar ao mês</a>
            <div class="rounded-2xl border border-slate-100 bg-white p-8 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-medium uppercase tracking-widest {{ $isPluggy ? 'text-blue-600' : 'text-slate-600' }}">{{ $isPluggy ? 'Importado pela Pluggy' : 'Lançamento manual' }}</p>
                        <h1 class="mt-2 text-3xl font-light text-slate-900">Detalhes da {{ $isIncome ? 'receita' : 'despesa' }}</h1>
                    </div>
                    <a href="{{ $isPluggy ? route('pluggy.transactions.edit', $transaction) : route('expenses.edit', $transaction) }}" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700">Editar</a>
                </div>

                <dl class="mt-8 grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">Descrição</dt><dd class="mt-1 rounded-xl bg-slate-50 p-3 text-slate-900">{{ $transaction->description }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">Data</dt><dd class="mt-1 rounded-xl bg-slate-50 p-3 text-slate-900">{{ $date->format('d/m/Y') }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">Valor</dt><dd class="mt-1 rounded-xl bg-slate-50 p-3 font-semibold {{ $isIncome ? 'text-emerald-700' : 'text-rose-700' }}">R$ {{ number_format($amount, 2, ',', '.') }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">Categoria</dt><dd class="mt-1 rounded-xl bg-slate-50 p-3 text-slate-900">{{ $isPluggy ? $transaction->category_label : ($transaction->category?->name ?? 'Sem categoria') }}</dd></div>
                    @if ($isPluggy)
                        <div><dt class="text-sm font-medium text-slate-500">Conta</dt><dd class="mt-1 rounded-xl bg-slate-50 p-3 text-slate-900">{{ $transaction->account?->name ?? '—' }}</dd></div>
                        <div><dt class="text-sm font-medium text-slate-500">Forma de pagamento</dt><dd class="mt-1 rounded-xl bg-slate-50 p-3 text-slate-900">{{ $transaction->payment_label }} <span class="text-xs text-slate-500">({{ $transaction->user_modified_payment_method ? 'definida por você' : 'classificação automática' }})</span></dd></div>
                        <div><dt class="text-sm font-medium text-slate-500">Situação na instituição</dt><dd class="mt-1 rounded-xl bg-slate-50 p-3 text-slate-900">{{ $transaction->status === 'POSTED' ? 'Confirmada' : 'Pendente' }}</dd></div>
                    @else
                        <div><dt class="text-sm font-medium text-slate-500">Banco</dt><dd class="mt-1 rounded-xl bg-slate-50 p-3 text-slate-900">{{ $transaction->bank_label }}</dd></div>
                        @if (! $isIncome)<div><dt class="text-sm font-medium text-slate-500">Forma de pagamento</dt><dd class="mt-1 rounded-xl bg-slate-50 p-3 text-slate-900">{{ $transaction->payment_label }}</dd></div>@endif
                        @if ($transaction->is_installment)<div><dt class="text-sm font-medium text-slate-500">Parcelamento</dt><dd class="mt-1 rounded-xl bg-blue-50 p-3 text-blue-900">{{ $transaction->current_installment }} / {{ $transaction->total_installments }} parcelas</dd></div>@endif
                        @if ($transaction->pluggy_installment_source_id)<div><dt class="text-sm font-medium text-slate-500">Compra vinculada</dt><dd class="mt-1 rounded-xl bg-slate-50 p-3">@if($transaction->pluggyInstallmentSource)<a class="text-blue-700 underline" href="{{ route('pluggy.transactions.show', $transaction->pluggyInstallmentSource) }}">Ver transação importada</a>@else Transação importada indisponível @endif</dd></div>@endif
                    @endif
                </dl>

                @if ($isPluggy && $transaction->is_installment)
                    <section class="mt-8 border-t border-slate-100 pt-6">
                        <h2 class="text-lg font-semibold">Revisão de parcelas</h2>
                        @foreach ($installmentCandidates as $candidate)
                            <div class="mt-4 rounded-xl bg-amber-50 p-4">
                                <p class="font-medium">Possível parcela duplicada</p>
                                <p class="mt-1 text-sm">Atual: {{ $transaction->date->format('d/m/Y') }} · {{ $transaction->status === 'POSTED' ? 'Confirmada' : 'Pendente' }} · parcela {{ $transaction->current_installment }} · R$ {{ number_format($transaction->amount, 2, ',', '.') }}</p>
                                <p class="mt-1 text-sm">Outra: {{ $candidate->description }} · {{ $candidate->date->format('d/m/Y') }} · {{ $candidate->status === 'POSTED' ? 'Confirmada' : 'Pendente' }} · parcela {{ $candidate->current_installment }} · R$ {{ number_format($candidate->amount, 2, ',', '.') }}</p>
                                <p class="mt-2 text-sm text-amber-800">Confira os lançamentos antes de excluir: eles podem ser compras diferentes.</p>
                                <a href="{{ route('pluggy.transactions.show', $candidate) }}" class="mt-3 inline-block text-sm font-medium text-blue-700 underline">Ver outra transação</a>
                            </div>
                        @endforeach
                        @foreach ($manualInstallmentCandidates as $candidate)
                            <div class="mt-4 rounded-xl bg-amber-50 p-4"><p class="font-medium">Possível parcela manual duplicada</p><p class="text-sm">{{ $candidate->description }} · {{ $candidate->date->format('d/m/Y') }} · R$ {{ number_format($candidate->amount, 2, ',', '.') }}</p><p class="mt-2 text-sm text-amber-800">Confira se são a mesma parcela antes de excluir um dos lançamentos.</p><a href="{{ route('expenses.show', $candidate) }}" class="mt-3 inline-block text-sm font-medium text-blue-700 underline">Ver parcela manual</a></div>
                        @endforeach
                        @if($transaction->purchase_date && $transaction->total_installments)
                            <details class="mt-4"><summary class="cursor-pointer text-sm font-medium text-blue-700">Adicionar parcela ausente</summary>
                                <form method="POST" action="{{ route('pluggy.transactions.manual-installment', $transaction) }}" class="mt-3 grid gap-3 sm:grid-cols-2">@csrf
                                    <label class="text-sm">Data da parcela<input type="date" name="date" required class="mt-1 block w-full rounded-lg border-slate-300"></label>
                                    <label class="text-sm">Valor<input type="number" name="amount" min="0.01" max="99999999.99" step="0.01" required class="mt-1 block w-full rounded-lg border-slate-300"></label>
                                    <label class="text-sm">Número da parcela<input type="number" name="number" min="1" max="{{ $transaction->total_installments }}" required class="mt-1 block w-full rounded-lg border-slate-300"></label>
                                    <label class="text-sm">Categoria<select name="category_id" required class="mt-1 block w-full rounded-lg border-slate-300"><option value="">Selecione</option>@foreach($expenseCategories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></label>
                                    <button class="rounded-lg bg-blue-600 px-3 py-2 text-sm text-white">Adicionar parcela</button>
                                </form>
                            </details>
                        @endif
                    </section>
                @endif

                @if ($isPluggy)
                    <form class="mt-8 border-t border-slate-100 pt-6" method="POST" action="{{ route('pluggy.transactions.destroy', $transaction) }}" onsubmit="return confirm('Remover este lançamento importado desta aplicação? Ele não será restaurado nas próximas sincronizações.')">
                        @csrf @method('DELETE')
                        <button class="rounded-xl border border-red-200 px-4 py-2.5 text-sm font-medium text-red-700 hover:bg-red-50">Excluir lançamento</button>
                    </form>
                @else
                    <a href="{{ route('expenses.confirm-delete', $transaction) }}" class="mt-8 inline-block border-t border-slate-100 pt-6 text-sm font-medium text-red-700 hover:text-red-900">Excluir lançamento</a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
