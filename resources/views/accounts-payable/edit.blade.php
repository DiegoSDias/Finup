<x-app-layout>
    <div class="min-h-screen bg-slate-50 py-8"><div class="mx-auto max-w-2xl px-4">
        <a href="{{ route('accounts-payable.show', $payable) }}" class="text-sm text-blue-700 hover:underline">← Voltar para a conta</a>
        <h1 class="mt-3 text-3xl font-light text-slate-900">Editar conta a pagar</h1>
        <form method="POST" action="{{ route('accounts-payable.update', $payable) }}" class="mt-6 rounded-2xl bg-white p-6 shadow-sm">@csrf @method('PUT') @include('accounts-payable._form') <button class="mt-6 rounded-xl bg-blue-600 px-5 py-3 font-medium text-white hover:bg-blue-700">Salvar alterações</button></form>
    </div></div>
</x-app-layout>
