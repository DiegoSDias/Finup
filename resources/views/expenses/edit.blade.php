<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
        <div class="max-w-3xl mx-auto px-4">
            
            {{-- Header --}}
            <div class="mb-6 animate-fade-in">
                <a href="{{ url()->previous() }}" 
                   class="group inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 transition-colors mb-4">
                    <svg class="w-5 h-5 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span class="text-sm font-medium">Voltar</span>
                </a>

                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl {{ $expense->type === 'income' ? 'bg-gradient-to-br from-emerald-500 to-green-500' : 'bg-gradient-to-br from-rose-500 to-red-500' }} flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-light text-slate-900">
                            Editar {{ $expense->type === 'income' ? 'Receita' : 'Despesa' }}
                        </h1>
                        <p class="text-slate-500 text-sm">Atualize as informações necessárias</p>
                    </div>
                </div>
            </div>

            {{-- Formulário --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 animate-slide-up">
                <form action="{{ route('expenses.update', $expense) }}" method="POST">
                    @csrf
                    @method('PUT')

                    @if ($expense->installments_group)
                        <label for="installmentScope" class="block text-sm font-semibold text-slate-700 mb-2">Aplicar alterações a</label>
                        <select id="installmentScope" name="installment_scope" class="w-full rounded-xl border-slate-200 mb-4">
                            <option value="single" @selected(old('installment_scope', 'single') === 'single')>Somente esta parcela</option>
                            <option value="group" @selected(old('installment_scope') === 'group')>Todo o grupo de parcelas</option>
                        </select>
                        <p class="text-sm text-slate-600 mb-4">Para o grupo, informe o valor total e a data da primeira parcela. As parcelas serão recriadas com o banco e a forma de pagamento informados, substituindo diferenças anteriores.</p>
                    @endif

                    @if ($expense->recurring_expense_id && $recurringExpense?->is_active)
                        <label for="recurrenceScope" class="block text-sm font-semibold text-slate-700 mb-2">Aplicar alterações a</label>
                        <select id="recurrenceScope" name="recurrence_scope" class="w-full rounded-xl border-slate-200 mb-2">
                            <option value="single" @selected(old('recurrence_scope', 'single') === 'single')>Somente esta ocorrência</option>
                            <option value="future" @selected(old('recurrence_scope') === 'future')>Esta e as próximas</option>
                        </select>
                        <p class="text-sm text-slate-600 mb-4">Para “esta e as próximas”, a série mantém seu calendário original; use “somente esta ocorrência” para mudar a data. Exceções futuras que você já editou ou excluiu individualmente serão preservadas.</p>
                    @elseif ($expense->recurring_expense_id)
                        <p class="text-sm text-slate-600 mb-4">Esta recorrência já foi encerrada. As alterações serão aplicadas somente a esta ocorrência.</p>
                    @endif

                    @include('expenses._form', [
                        'expense' => $expense,
                        'type' => $expense->type
                    ])

                    {{-- Botões --}}
                    <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-slate-200">
                        <a href="{{ url()->previous() }}" 
                           class="px-6 py-3 rounded-xl bg-white border border-slate-200 text-slate-700 font-medium hover:bg-slate-50 hover:border-slate-300 transition-all duration-200 shadow-sm hover:shadow-md">
                            Cancelar
                        </a>

                        <button type="submit" 
                                class="group px-6 py-3 rounded-xl {{ $expense->type === 'income' ? 'bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-700 hover:to-emerald-600' : 'bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-700 hover:to-rose-600' }} text-white font-medium transition-all duration-200 shadow-sm hover:shadow-md flex items-center gap-2">
                            <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Atualizar
                        </button>
                    </div>
                </form>
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
            animation: slide-up 0.8s ease-out;
        }
    </style>

    <script>
        @if ($expense->installments_group)
        const scopeSelect = document.getElementById('installmentScope');
        const amountInput = document.querySelector('input[name="amount"]');
        const dateInput = document.querySelector('input[name="date"]');
        const scopeDefaults = {
            single: {amount: @json($expense->amount), date: @json($expense->date->format('Y-m-d'))},
            group: {amount: @json($groupTotal), date: @json($groupDate)}
        };
        scopeSelect.addEventListener('change', function () {
            amountInput.value = scopeDefaults[this.value].amount;
            dateInput.value = scopeDefaults[this.value].date;
        });
        @endif
        @if ($expense->recurring_expense_id && $recurringExpense?->is_active)
        const recurrenceScope = document.getElementById('recurrenceScope');
        const recurrenceDate = document.querySelector('input[name="date"]');
        function syncRecurrenceDate() {
            const appliesToFuture = recurrenceScope.value === 'future';
            recurrenceDate.readOnly = appliesToFuture;
            recurrenceDate.classList.toggle('bg-slate-100', appliesToFuture);
            recurrenceDate.title = appliesToFuture ? 'A data da série é mantida ao alterar esta e as próximas ocorrências.' : '';
        }
        recurrenceScope.addEventListener('change', syncRecurrenceDate);
        syncRecurrenceDate();
        @endif
        const categorySelect = document.getElementById('categorySelect')
        const newCategoryField = document.getElementById('newCategoryField')

        categorySelect.addEventListener('change', function() {
            if (this.value === 'new') {
                newCategoryField.classList.remove('hidden')
            } else {
                newCategoryField.classList.add('hidden')
            }
        })

    </script>
</x-app-layout>
