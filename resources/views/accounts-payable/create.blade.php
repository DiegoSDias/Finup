<x-app-layout>
    <div class="min-h-screen bg-slate-50 py-8"><div class="mx-auto max-w-2xl px-4">
        <a href="{{ route('accounts-payable.index') }}" class="text-sm text-blue-700 hover:underline">← Contas a pagar</a>
        <h1 class="mt-3 text-3xl font-light text-slate-900">Nova conta a pagar</h1>
        <p class="mt-2 text-sm text-slate-500">A conta é uma previsão e não entra no saldo realizado até ser liquidada.</p>
        <form method="POST" action="{{ route('accounts-payable.store') }}" class="mt-6 rounded-2xl bg-white p-6 shadow-sm">@csrf @include('accounts-payable._form') <button class="mt-6 rounded-xl bg-blue-600 px-5 py-3 font-medium text-white hover:bg-blue-700">Cadastrar conta</button></form>
    </div></div>
</x-app-layout>
