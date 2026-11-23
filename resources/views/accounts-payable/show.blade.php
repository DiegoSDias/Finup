<x-app-layout>
    <div class="min-h-screen bg-slate-50 py-8"><div class="mx-auto max-w-4xl px-4">
        <a href="{{ route('accounts-payable.index') }}" class="text-sm text-blue-700 hover:underline">← Contas a pagar</a>
        @php($labels = ['pending' => 'Pendente', 'overdue' => 'Vencida', 'paid' => 'Paga', 'cancelled' => 'Cancelada'])
        <section class="mt-4 rounded-2xl bg-white p-6 shadow-sm"><div class="flex flex-wrap justify-between gap-4"><div><h1 class="text-3xl font-light text-slate-900">{{ $payable->description }}</h1><p class="mt-2 text-sm text-slate-500">Vencimento: {{ $payable->due_date->format('d/m/Y') }} · {{ $payable->category?->name ?? 'Sem categoria' }}</p></div><div class="text-right"><p class="text-2xl font-semibold text-slate-900">R$ {{ number_format((float) $payable->amount, 2, ',', '.') }}</p><p class="mt-1 text-sm {{ $payable->display_status === 'overdue' ? 'text-rose-700' : 'text-slate-500' }}">{{ $labels[$payable->display_status] }}</p></div></div>
            @if ($payable->isPending())
                <div class="mt-5 flex flex-wrap gap-3 border-t pt-5">
                    <a href="{{ route('accounts-payable.edit', $payable) }}" class="rounded-lg border px-4 py-2 text-sm text-slate-700">Editar</a>
                    <form method="POST" action="{{ route('accounts-payable.cancel', $payable) }}">@csrf <button class="rounded-lg border border-amber-200 px-4 py-2 text-sm text-amber-800">Cancelar</button></form>
                    <form method="POST" action="{{ route('accounts-payable.destroy', $payable) }}" onsubmit="return confirm('Excluir esta conta a pagar?')">@csrf @method('DELETE') <button class="rounded-lg border border-rose-200 px-4 py-2 text-sm text-rose-700">Excluir</button></form>
                </div>
            @elseif ($payable->status === 'cancelled')
                <form method="POST" action="{{ route('accounts-payable.destroy', $payable) }}" class="mt-5 border-t pt-5" onsubmit="return confirm('Excluir esta conta cancelada?')">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-lg border border-rose-200 px-4 py-2 text-sm text-rose-700">Excluir conta cancelada</button>
                </form>
            @endif
        </section>
        @if ($payable->status === 'paid')
            <section class="mt-6 rounded-2xl border border-emerald-100 bg-emerald-50 p-6"><h2 class="font-semibold text-emerald-900">Pagamento registrado</h2><p class="mt-1 text-sm text-emerald-800">@if($payable->settlement->manual_expense_id) Foi criada uma despesa manual vinculada. @else Esta conta foi vinculada a uma transação importada da Pluggy, sem criar outra despesa. @endif</p><form method="POST" action="{{ route('accounts-payable.reopen', $payable) }}" class="mt-4">@csrf <button class="rounded-lg border border-emerald-300 px-4 py-2 text-sm font-medium text-emerald-800">Desfazer liquidação</button></form></section>
        @elseif ($payable->isPending())
            <section class="mt-6 rounded-2xl bg-white p-6 shadow-sm"><h2 class="text-xl font-semibold text-slate-900">Registrar pagamento</h2><p class="mt-1 text-sm text-slate-500">Escolha uma única origem: registrar uma despesa manual ou vincular uma transação já importada.</p>
                @if (! $payable->category_id)<p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Defina uma categoria antes de registrar um pagamento manual.</p>@else
                <form method="POST" action="{{ route('accounts-payable.settle-manually', $payable) }}" class="mt-5 grid gap-4 border-t pt-5 sm:grid-cols-2">@csrf
                    <div><label class="block text-sm font-medium">Data de pagamento</label><input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" class="mt-1 w-full rounded-lg border-slate-300"></div>
                    <div><label class="block text-sm font-medium">Forma de pagamento</label><select name="payment_mode" class="mt-1 w-full rounded-lg border-slate-300"><option value="pix">Pix</option><option value="dinheiro">Dinheiro</option><option value="debito">Cartão de débito</option><option value="credito">Cartão de crédito</option></select></div>
                    <div class="sm:col-span-2"><label class="block text-sm font-medium">Banco de origem</label><select name="bank_choice" class="mt-1 w-full rounded-lg border-slate-300"><option value="none">Não realizado por meio de banco</option>@foreach($banks as $bank)<option value="{{ $bank->id }}">{{ $bank->name }}</option>@endforeach<option value="new">Cadastrar novo banco</option></select><input name="new_bank" class="mt-2 w-full rounded-lg border-slate-300" placeholder="Nome do novo banco (se selecionado acima)"></div>
                    <div class="sm:col-span-2"><button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white">Registrar despesa manual</button></div>
                </form>@endif
                @if ($eligibleTransactions->isNotEmpty())<form method="POST" action="{{ route('accounts-payable.settle-pluggy', $payable) }}" class="mt-6 border-t pt-5">@csrf<label class="block text-sm font-medium">Ou vincular transação da Pluggy</label><select name="pluggy_transaction_id" class="mt-1 w-full rounded-lg border-slate-300">@foreach($eligibleTransactions as $transaction)<option value="{{ $transaction->id }}">{{ $transaction->transaction_date->format('d/m/Y') }} · R$ {{ number_format((float) $transaction->amount, 2, ',', '.') }} · {{ $transaction->description }}</option>@endforeach</select><button class="mt-3 rounded-lg border border-blue-200 px-4 py-2 text-sm font-medium text-blue-700">Vincular sem criar despesa</button></form>@endif
            </section>
        @endif
    </div></div>
</x-app-layout>
