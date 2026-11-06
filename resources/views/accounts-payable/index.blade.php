<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8"><div class="mx-auto max-w-5xl px-4">
        <div class="flex flex-wrap items-center justify-between gap-4"><div><p class="text-sm font-medium uppercase tracking-widest text-blue-600">Planejamento</p><h1 class="mt-1 text-3xl font-light text-slate-900">Contas a pagar</h1><p class="mt-2 text-sm text-slate-500">Compromissos pendentes ficam separados das despesas já realizadas.</p></div><a href="{{ route('accounts-payable.create') }}" class="rounded-xl bg-blue-600 px-5 py-3 font-medium text-white hover:bg-blue-700">Nova conta</a></div>
        <div class="mt-8 space-y-3">
            @forelse ($payables as $payable)
                @php($labels = ['pending' => 'Pendente', 'overdue' => 'Vencida', 'paid' => 'Paga', 'cancelled' => 'Cancelada'])
                <a href="{{ route('accounts-payable.show', $payable) }}" class="block rounded-2xl border border-slate-100 bg-white p-5 shadow-sm hover:border-blue-200"><div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="font-semibold text-slate-900">{{ $payable->description }}</h2><p class="mt-1 text-sm text-slate-500">Vence em {{ $payable->due_date->format('d/m/Y') }} · {{ $payable->category?->name ?? 'Sem categoria' }}</p></div><div class="text-right"><p class="font-semibold text-slate-900">R$ {{ number_format((float) $payable->amount, 2, ',', '.') }}</p><span class="text-sm {{ $payable->display_status === 'overdue' ? 'text-rose-700' : 'text-slate-500' }}">{{ $labels[$payable->display_status] }}</span></div></div></a>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">Nenhuma conta a pagar cadastrada.</div>
            @endforelse
        </div>
    </div></div>
</x-app-layout>
