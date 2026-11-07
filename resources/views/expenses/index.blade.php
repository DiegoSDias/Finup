<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <x-back-button />
        
        {{-- Header --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 animate-fade-in">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <x-months-options :year="$year" :month="$month"/>

                <div class="flex gap-3">
                    <a href="{{ route('expenses.create', ['type' => 'income', 'year' => $year, 'month' => $month]) }}"
                       class="group px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 text-white font-medium hover:from-emerald-700 hover:to-emerald-600 transition-all duration-200 shadow-sm hover:shadow-md flex items-center gap-2">
                        <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Receita
                    </a>

                    <a href="{{ route('expenses.create', ['type' => 'expense', 'year' => $year, 'month' => $month]) }}"
                       class="group px-5 py-2.5 rounded-xl bg-gradient-to-r from-rose-600 to-rose-500 text-white font-medium hover:from-rose-700 hover:to-rose-600 transition-all duration-200 shadow-sm hover:shadow-md flex items-center gap-2">
                        <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Despesa
                    </a>
                    <a href="{{ route('recurring-expenses.index') }}" class="group px-5 py-2.5 rounded-xl border border-rose-200 bg-white text-rose-700 font-medium hover:bg-rose-50 transition-all flex items-center gap-2">↻ Recorrências</a>
                </div>
            </div>
        </div>

        {{-- RECEITAS --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden animate-slide-up" style="animation-delay: 0.1s">
            <div class="bg-gradient-to-r from-emerald-50 via-green-50 to-emerald-50 p-6 border-b border-emerald-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-light text-emerald-900">Receitas</h2>
                </div>
            </div>
            
            <x-filter-incomes :categoriesIncome="$categoriesIncome"/>

            @if ($typeIncomes->isEmpty() && !$balanceMonthPrevious)
                <div class="p-16 text-center">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-slate-50 flex items-center justify-center">
                        <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                    <p class="text-slate-500 font-medium text-lg">Nenhuma receita cadastrada</p>
                    <p class="text-slate-400 text-sm mt-1">Adicione uma receita para começar</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Categoria</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Valor</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider w-32">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @if($balanceMonthPrevious)
                                <tr class="hover:bg-emerald-50/30 transition-colors group">
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                            Sobra do mês passado
                                        </span>
                                    </td>
                                    <td class="px-6 py-4"></td>
                                    <td class="px-6 py-4 text-center text-emerald-700 font-semibold text-base">
                                        R$ {{ number_format($balanceMonthPrevious, 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4"></td>
                                </tr>
                            @endif
                            @foreach ($typeIncomes as $income)
                                <tr class="hover:bg-emerald-50/30 transition-colors group">
                                    <td class="px-6 py-4 items-center">
                                        @if($income->category)
                                            <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 text-slate-700">
                                                {{ $income->category->name }}
                                            </span>
                                        @else
                                            <span class="text-slate-400 text-xs">—</span>
                                        @endif
                                        @if($income instanceof \App\Models\PluggyTransaction)
                                            <span class="block text-xs text-slate-500 mt-1">{{ $income->connection?->display_label ?? 'Instituição não informada' }}</span>
                                        @else
                                            <span class="block text-xs text-slate-500 mt-1">{{ $income->bank_label }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center text-emerald-700 font-semibold text-base">
                                        {{ $income->date->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-emerald-700 font-semibold text-base">
                                        R$ {{ number_format($income->amount, 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            @if($income instanceof \App\Models\PluggyTransaction)
                                                <a href="{{ route('pluggy.transactions.show', $income) }}" class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors" title="Visualizar">◉</a>
                                                <a href="{{ route('pluggy.transactions.edit', $income) }}" class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors" title="Editar">✎</a>
                                                <form action="{{ route('pluggy.transactions.destroy', $income) }}" method="POST" onsubmit="return confirm('Remover esta receita importada desta aplicação?')" class="inline">@csrf @method('DELETE')<button class="p-2 rounded-lg text-red-600 hover:bg-red-50 transition-colors" title="Excluir">⌫</button></form>
                                            @else
                                            <a href="{{ route('expenses.show', $income) }}"
                                               class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors" title="Editar">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </a>
                                            <a href="{{ route('expenses.edit', $income) }}" 
                                               class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors" title="Editar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                            @if ($income->installments_group)
                                            <a href="{{ route('expenses.confirm-delete', $income) }}" class="p-2 rounded-lg text-red-600 hover:bg-red-50 transition-colors" title="Excluir" aria-label="Excluir receita">⌫</a>
                                            @else
                                            <form action="{{ route('expenses.destroy', $income) }}" method="POST"
                                                onsubmit="return confirm('Tem certeza que deseja excluir esta receita?')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 rounded-lg text-red-600 hover:bg-red-50 transition-colors" title="Excluir">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                            @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="bg-emerald-50 border-t-2 border-emerald-200">
                                <td class="px-6 py-4 font-bold text-slate-800 text-sm uppercase tracking-wide" colspan="3">Total de Receitas</td>
                                <td class="px-6 py-4 text-right text-emerald-700 font-bold text-xl">
                                    R$ {{ number_format($filteredIncomesTotal, 2, ',', '.') }}
                                </td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- DESPESAS --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden animate-slide-up" style="animation-delay: 0.2s">
            <div class="bg-gradient-to-r from-rose-50 via-red-50 to-rose-50 p-6 border-b border-rose-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-rose-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-light text-rose-900">Despesas</h2>
                </div>
            </div>

            <x-filter-expenses :categoriesExpense="$categoriesExpense"/>

            @if ($typeExpenses->isEmpty())
                <div class="p-16 text-center">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-slate-50 flex items-center justify-center">
                        <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <p class="text-slate-500 font-medium text-lg">Nenhuma despesa cadastrada</p>
                    <p class="text-slate-400 text-sm mt-1">Adicione uma despesa para começar</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Categoria</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Pagamento</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Valor</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Parcelas</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider w-32">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($typeExpenses as $expense)
                                <tr class="hover:bg-rose-50/30 transition-colors group">
                                    <td class="px-6 py-4">
                                        @if($expense->category)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 text-slate-700">
                                                {{ $expense->category->name }}
                                            </span>
                                        @else
                                            <span class="text-slate-400 text-xs">—</span>
                                        @endif
                                        @if($expense instanceof \App\Models\Expense && $expense->recurring_expense_id)
                                            <span class="ml-2 inline-flex px-2 py-1 rounded-lg text-xs font-medium bg-violet-50 text-violet-700">Recorrente</span>
                                        @endif
                                        @if($expense instanceof \App\Models\PluggyTransaction)
                                            <span class="block text-xs text-slate-500 mt-1">{{ $expense->connection?->display_label ?? 'Instituição não informada' }}</span>
                                        @else
                                            <span class="block text-xs text-slate-500 mt-1">{{ $expense->bank_label }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 items-center text-center">
                                        <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-medium
                                            {{ $expense->payment_mode === 'pix' ? 'bg-purple-50 text-purple-700 border border-purple-100' : 'bg-amber-50 text-amber-700 border border-amber-100' }}">
                                            {{ $expense->payment_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center text-emerald-700 font-semibold text-base">
                                        {{ $expense->date->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-rose-700 font-semibold text-base">
                                        R$ {{ number_format($expense instanceof \App\Models\PluggyTransaction ? $expense->display_amount : $expense->amount, 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($expense->current_installment)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                                {{ $expense->current_installment }}
                                            </span>
                                        @else
                                            <span class="text-slate-400 text-xs">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            @if($expense instanceof \App\Models\PluggyTransaction)
                                                <a href="{{ route('pluggy.transactions.show', $expense) }}" class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors" title="Visualizar">◉</a>
                                                <a href="{{ route('pluggy.transactions.edit', $expense) }}" class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors" title="Editar">✎</a>
                                                <form action="{{ route('pluggy.transactions.destroy', $expense) }}" method="POST" onsubmit="return confirm('Remover esta despesa importada desta aplicação?')" class="inline">@csrf @method('DELETE')<button class="p-2 rounded-lg text-red-600 hover:bg-red-50 transition-colors" title="Excluir">⌫</button></form>
                                            @else
                                            <a href="{{ route('expenses.show', $expense) }}"
                                               class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors" title="Visualizar">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </a>
                                            <a href="{{ route('expenses.edit', $expense) }}" 
                                               class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors" title="Editar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                            @if ($expense->installments_group)
                                            <a href="{{ route('expenses.confirm-delete', $expense) }}" class="p-2 rounded-lg text-red-600 hover:bg-red-50 transition-colors" title="Excluir" aria-label="Excluir despesa">⌫</a>
                                            @else
                                            <form action="{{ route('expenses.destroy', $expense) }}" method="POST"
                                                onsubmit="return confirm('Tem certeza que deseja excluir esta despesa?')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 rounded-lg text-red-600 hover:bg-red-50 transition-colors" title="Excluir">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                            @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="bg-rose-50 border-t-2 border-rose-200">
                                <td class="px-6 py-4 font-bold text-slate-800 text-sm uppercase tracking-wide" colspan="4">Total de Despesas</td>
                                <td class="px-6 py-4 text-right text-rose-700 font-bold text-xl">
                                    R$ {{ number_format($filteredExpensesTotal, 2, ',', '.') }}
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if ($excludedTransactions->isNotEmpty())
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden animate-slide-up" style="animation-delay: 0.25s">
                <div class="p-6 border-b border-slate-100"><h2 class="text-2xl font-light text-slate-900">Movimentações não contabilizadas</h2><p class="mt-1 text-sm text-slate-500">Cada lançamento abaixo informa exatamente por que ainda não entra nos totais.</p></div>
                <div class="divide-y divide-slate-100">
                    @foreach ($excludedTransactions as $transaction)
                        <div class="flex flex-col gap-2 p-5 sm:flex-row sm:items-center sm:justify-between">
                            @php
                                $reason = match (true) {
                                    $transaction->status === 'PENDING' => 'Pendente na instituição: entrará no total quando for confirmada.',
                                    in_array($transaction->exclusion_reason, ['user_exclusion_phrase', 'user_transfer_phrase']) => 'Bloqueada por uma regra personalizada.',
                                    $transaction->classification === 'internal_reserve_transfer' => 'Movimentação de saldo reservado/caixinha.',
                                    $transaction->classification === 'internal_transfer' => 'Transferência entre as suas contas.',
                                    $transaction->classification === 'credit_card_payment' => 'Pagamento de fatura (evita contar a mesma compra duas vezes).',
                                    $transaction->classification === 'ignored' => 'Excluída por uma regra personalizada.',
                                    $transaction->classification === 'unknown' => 'Sem tipo informado pela instituição; revise este lançamento.',
                                    default => 'Não contabilizada por classificação pendente de revisão.',
                                };
                            @endphp
                            <div><p class="font-medium text-slate-800">{{ $transaction->description }}</p><p class="text-sm text-slate-500">{{ $transaction->transaction_date->format('d/m/Y') }} · {{ $reason }}</p></div>
                            <span class="font-semibold text-slate-700">R$ {{ number_format($transaction->amount, 2, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- RESUMO --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden animate-slide-up" style="animation-delay: 0.3s">
            <div class="bg-gradient-to-r from-indigo-50 via-blue-50 to-indigo-50 p-6 border-b border-indigo-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-light text-indigo-900">Resumo do Mês</h2>
                </div>
            </div>

            <div class="p-8 space-y-5">
                <div class="flex justify-between items-center py-4 border-b border-slate-100">
                    <span class="text-slate-600 font-medium flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                        Entradas do mês
                    </span>
                    <span class="text-emerald-700 font-bold text-xl">
                        R$ {{ number_format($incomesBalance, 2, ',', '.') }}
                    </span>
                </div>

                <div class="flex justify-between items-center py-4 border-b border-slate-100">
                    <span class="text-slate-600 font-medium flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-purple-500"></div>
                        Despesas pagas à vista (Pix)
                    </span>
                    <span class="text-purple-700 font-bold text-xl">
                        R$ {{ number_format($expensesPix, 2, ',', '.') }}
                    </span>
                </div>

                <div class="border-t-2 border-slate-200 pt-5"></div>

                <div class="flex justify-between items-center py-4 bg-gradient-to-r from-emerald-50 to-green-50 rounded-xl px-5 border border-emerald-100">
                    <span class="text-slate-800 font-semibold text-base">Saldo líquido disponível</span>
                    <span class="text-emerald-700 font-bold text-2xl">
                        R$ {{ number_format(($incomesBalance - $expensesPix), 2, ',', '.') }}
                    </span>
                </div>

                <div class="flex justify-between items-center py-4 bg-gradient-to-r from-rose-50 to-red-50 rounded-xl px-5 border border-rose-100">
                    <span class="text-slate-800 font-semibold text-base">Total de despesas do mês</span>
                    <span class="text-rose-700 font-bold text-2xl">
                        R$ {{ number_format($expensesBalance, 2, ',', '.') }}
                    </span>
                </div>

                <div class="border-t-2 border-slate-200 pt-5"></div>

                <div class="flex justify-between items-center py-5 bg-gradient-to-r {{ $balanceAvailable >= 0 ? 'from-blue-50 to-indigo-50 border-blue-200' : 'from-red-50 to-rose-50 border-red-200' }} rounded-xl px-6 border-2">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Saldo Final</p>
                        <span class="text-slate-900 font-bold text-lg">{{ date('m/Y', mktime(0, 0, 0, $month, 1, $year)) }}</span>
                    </div>
                    <span class="font-bold text-3xl {{ $balanceAvailable >= 0 ? 'text-blue-700' : 'text-red-700' }}">
                        R$ {{ number_format($balanceAvailable, 2, ',', '.') }}
                    </span>
                </div>

                <form action="{{ route('month.close', [$year, $month]) }}" method="POST" class="mt-8">
                    @csrf
                    <button class="group w-full px-6 py-4 bg-gradient-to-r from-indigo-600 to-indigo-500 text-white rounded-xl font-semibold hover:from-indigo-700 hover:to-indigo-600 transition-all duration-200 shadow-sm hover:shadow-lg flex items-center justify-center gap-2">
                        <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Fechar Mês
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    * {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slide-up {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in {
        animation: fade-in 0.6s ease-out;
    }

    .animate-slide-up {
        animation: slide-up 0.8s ease-out backwards;
    }
</style>
