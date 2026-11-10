<x-app-layout>
    @php
        $accounts = $connections->flatMap->accounts;
        $totalBalance = $accounts->where('type', 'BANK')->sum('balance');
        $creditBalance = $accounts->where('type', 'CREDIT')->sum('balance');
    @endphp

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-6 sm:p-8">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-10">
                <div><p class="text-sm font-medium uppercase tracking-widest text-blue-600 mb-2">Open Finance</p><h1 class="text-4xl font-light tracking-tight text-slate-900">Minhas conexões</h1><p class="mt-2 text-slate-500">Dados sincronizados localmente pela Pluggy.</p></div>
                <div class="flex flex-wrap gap-3">
                    @if ($connections->isNotEmpty())
                        <form method="POST" action="{{ route('pluggy.connections.sync-all') }}" data-sync-form>@csrf<button class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700">Sincronizar todos</button></form>
                    @endif
                    <a href="{{ route('pluggy.data-notice') }}" class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-medium text-blue-700 shadow-sm hover:bg-blue-100">Sobre os dados importados</a>
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">Voltar ao painel</a>
                </div>
            </div>

            @if (session('success'))<div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>@endif
            @if (session('warning'))<div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-800">{{ session('warning') }}</div>@endif
            @if ($errors->any())<div role="alert" class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800">{{ $errors->first() }}</div>@endif

            <p id="sync-update-message" class="mb-4 text-sm text-slate-600" role="status" aria-live="polite"></p>

            <div id="connection-overview">
            @if ($connections->isEmpty())
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white/70 px-6 py-16 text-center shadow-sm"><h2 class="text-xl font-medium text-slate-900">Nenhuma conexão vinculada</h2><p class="mx-auto mt-2 max-w-md text-slate-500">Vincule um Item ID já existente ou conecte uma nova instituição no painel.</p><a href="{{ route('dashboard') }}" class="mt-6 inline-flex rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700">Ir para o painel</a></div>
            @else
                <div class="grid gap-5 md:grid-cols-2 mb-10"><div class="rounded-3xl bg-slate-900 p-6 text-white shadow-xl shadow-slate-200"><p class="text-sm text-slate-300">Saldo disponível</p><p class="mt-3 text-3xl font-light">R$ {{ number_format($totalBalance, 2, ',', '.') }}</p></div><div class="rounded-3xl border border-amber-100 bg-amber-50 p-6 shadow-sm"><p class="text-sm text-amber-800">Fatura em aberto</p><p class="mt-3 text-3xl font-light text-amber-950">R$ {{ number_format($creditBalance, 2, ',', '.') }}</p></div></div>

                <div class="space-y-5 mb-10">
                    @foreach ($connections as $connection)
                        <section data-sync-status="{{ $connection->last_sync_status }}" class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-4">
                                    @if ($connection->connector_image_url)<img src="{{ $connection->connector_image_url }}" class="h-11 w-11 rounded-xl" alt="">@else<div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 text-blue-700">⌁</div>@endif
                                    <div><h2 class="font-medium text-slate-900">{{ $connection->display_label }}</h2><p class="text-sm text-slate-500">{{ $connection->accounts->count() }} conta(s) · {{ $connection->last_synced_at?->format('d/m/Y H:i') ?? 'Aguardando primeira sincronização' }}</p><p class="mt-1 text-sm text-slate-700">{{ match ($connection->last_sync_status) { 'queued' => 'Aguardando na fila', 'running' => 'Sincronização em andamento', 'awaiting_provider' => 'Aguardando o provedor', 'success' => 'Sincronização concluída', 'partial' => 'Atualização parcial no provedor', 'failed' => 'Sincronização falhou', 'needs_reconnection', 'not_found' => 'Conexão precisa de revisão', 'needs_action' => 'A instituição solicita uma ação', default => 'Estado de sincronização não informado' } }}</p>@if ($connection->last_error)<p class="mt-1 text-sm text-red-600">{{ $connection->last_error }}</p>@endif</div>
                                </div>
                                <div class="flex gap-2"><form method="POST" action="{{ route('pluggy.connections.sync', $connection->id) }}" data-sync-form>@csrf<button @disabled(in_array($connection->last_sync_status, ['queued', 'running'], true)) class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">Sincronizar</button></form><form method="POST" action="{{ route('pluggy.connections.destroy', $connection->id) }}" onsubmit="return confirm('Remover esta conexão do sistema?')">@csrf @method('DELETE')<button class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Remover</button></form></div>
                            </div>
                            <details class="mt-4 border-t border-slate-100 pt-4">
                                <summary class="cursor-pointer text-sm font-medium text-blue-700">Editar nome da conexão</summary>
                                <form method="POST" action="{{ route('pluggy.connections.name', $connection->id) }}" class="mt-3 flex flex-wrap items-end gap-3">
                                    @csrf @method('PATCH')
                                    <label class="text-sm text-slate-700">Nome personalizado
                                        <input name="display_name" value="{{ $connection->display_name }}" maxlength="100" class="mt-1 block rounded-xl border-slate-200" placeholder="Ex.: Mercado Pago">
                                    </label>
                                    <button class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white">Salvar nome</button>
                                    <p class="w-full text-xs text-slate-500">Deixe vazio para usar o nome informado pela Pluggy.</p>
                                </form>
                            </details>
                        </section>
                    @endforeach
                </div>

                <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($accounts as $account)
                        @php($isCredit = $account->type === 'CREDIT')
                        <article class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm"><div class="flex items-start justify-between gap-3"><div class="flex h-11 w-11 items-center justify-center rounded-2xl {{ $isCredit ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700' }}">{{ $isCredit ? '◫' : '⌁' }}</div><span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $isCredit ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700' }}">{{ $isCredit ? 'Cartão' : 'Conta' }}</span></div><h2 class="mt-5 truncate text-lg font-medium text-slate-900">{{ $account->name }}</h2><p class="mt-1 text-sm text-slate-500">Final {{ $account->number_last_four ?? '—' }}</p><div class="mt-6 border-t border-slate-100 pt-4"><p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ $isCredit ? 'Fatura atual' : 'Saldo disponível' }}</p><p class="mt-1 text-2xl font-light text-slate-900">R$ {{ number_format($account->balance, 2, ',', '.') }}</p></div></article>
                    @endforeach
                </div>
            @endif
            </div>

            @if ($inactiveConnections->isNotEmpty())
                <section class="mt-10 rounded-3xl border border-slate-200 bg-white/80 p-6 shadow-sm">
                    <h2 class="text-lg font-medium text-slate-900">Conexões removidas ou indisponíveis</h2>
                    <p class="mt-1 text-sm text-slate-500">Se a conta ainda existe na Pluggy, reative-a aqui em vez de criar outra conexão.</p>
                    <div class="mt-5 space-y-3">
                        @foreach ($inactiveConnections as $connection)
                            <div class="flex flex-col gap-3 rounded-2xl border border-slate-100 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div><p class="font-medium text-slate-800">{{ $connection->display_label }}</p><p class="text-sm text-slate-500">{{ $connection->last_error ?? 'Conexão removida desta aplicação.' }}</p></div>
                                <form method="POST" action="{{ route('pluggy.connections.reactivate', $connection->id) }}">@csrf<button class="rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50">Reativar</button></form>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="mt-10 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-medium text-slate-900">Categorias por descrição</h2>
                <p class="mt-1 text-sm text-slate-500">A regra vale para todas as conexões e preserva categorias escolhidas individualmente. Regras mais específicas têm prioridade.</p>
                <form method="POST" action="{{ route('pluggy.category-rules.store') }}" class="mt-4 flex flex-wrap items-end gap-3" x-data="{ newCategory: @js(old('category_id') === 'new') }">
                    @csrf
                    <label class="text-sm text-slate-700">Descrição contém<input name="phrase" value="{{ old('phrase') }}" required minlength="3" maxlength="150" class="mt-1 block rounded-xl border-slate-200" placeholder="Ex.: Uber"></label>
                    <label class="text-sm text-slate-700">Categoria<select name="category_id" required class="mt-1 block rounded-xl border-slate-200" @change="newCategory = $event.target.value === 'new'"><option value="">Selecione</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }} ({{ $category->type === 'income' ? 'receita' : 'despesa' }})</option>@endforeach<option value="new" @selected(old('category_id') === 'new')>+ Criar nova categoria</option></select></label>
                    <label x-cloak x-show="newCategory" class="text-sm text-slate-700">Nome da nova categoria<input name="new_category" value="{{ old('new_category') }}" maxlength="100" :required="newCategory" class="mt-1 block rounded-xl border-slate-200" placeholder="Ex.: Transporte"></label>
                    <label x-cloak x-show="newCategory" class="text-sm text-slate-700">Tipo<select name="new_category_type" :required="newCategory" class="mt-1 block rounded-xl border-slate-200"><option value="">Selecione</option><option value="expense" @selected(old('new_category_type') === 'expense')>Despesa</option><option value="income" @selected(old('new_category_type') === 'income')>Receita</option></select></label>
                    <label class="text-sm text-slate-700">Válida a partir de (opcional)<input type="date" name="starts_on" value="{{ old('starts_on') }}" class="mt-1 block rounded-xl border-slate-200"></label>
                    <button class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white">Criar regra</button>
                </form>
                <div class="mt-5 space-y-3">
                    @foreach($categoryRules as $rule)
                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="font-medium">{{ $rule->phrase }} → {{ $rule->category?->name ?? 'Categoria indisponível' }}</p>
                            <p class="text-sm text-slate-500">Início: {{ $rule->starts_on?->format('d/m/Y') ?? 'todo o histórico' }} · Encerramento: {{ $rule->ends_before?->format('d/m/Y') ?? 'sem data' }} (dia não incluído)</p>
                            <details class="mt-2"><summary class="cursor-pointer text-sm text-blue-700">Editar regra</summary>
                                <form method="POST" action="{{ route('pluggy.category-rules.update', $rule) }}" class="mt-2 flex flex-wrap gap-2">@csrf @method('PATCH')
                                    <input name="phrase" value="{{ $rule->phrase }}" required minlength="3" maxlength="150" class="rounded-lg border-slate-200">
                                    <select name="category_id" required class="rounded-lg border-slate-200">@foreach($categories as $category)<option value="{{ $category->id }}" @selected($rule->category_id === $category->id)>{{ $category->name }} ({{ $category->type === 'income' ? 'receita' : 'despesa' }})</option>@endforeach</select>
                                    <input type="date" name="starts_on" value="{{ $rule->starts_on?->format('Y-m-d') }}" class="rounded-lg border-slate-200"><button class="rounded-lg bg-blue-600 px-3 py-2 text-white">Salvar</button>
                                </form>
                            </details>
                            <form method="POST" action="{{ route('pluggy.category-rules.destroy', $rule) }}" class="mt-3 flex flex-wrap items-end gap-2" onsubmit="return confirm('Reavaliar as categorias afetadas por esta alteração?')">@csrf @method('DELETE')
                                <label class="text-sm">Encerrar a partir de <input type="date" name="ends_before" class="rounded-lg border-slate-200"></label><button name="mode" value="end" class="rounded-lg border px-3 py-2 text-sm">Encerrar</button><button name="mode" value="delete" class="rounded-lg border border-red-200 px-3 py-2 text-sm text-red-700">Excluir regra e reavaliar histórico</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="mt-10 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="max-w-3xl">
                    <p class="text-sm font-medium uppercase tracking-widest text-blue-600">Regras de movimentação</p>
                    <h2 class="mt-1 text-xl font-medium text-slate-900">Excluir por frase</h2>
                    <p class="mt-1 text-sm text-slate-500">Use frases específicas. A regra vale para lançamentos já importados e para as próximas sincronizações.</p>
                </div>

                <form method="POST" action="{{ route('pluggy.transaction-rules.store') }}" class="mt-5 grid gap-3 md:grid-cols-[1fr_230px_auto]">
                    @csrf
                    <input name="phrase" required minlength="3" maxlength="150" value="{{ old('phrase') }}" class="rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ex.: Dinheiro reservado Guardar Dinheiro">
                    <select name="pluggy_connection_id" required class="rounded-xl border-slate-200 text-sm"><option value="">Escolha uma conexão</option>@foreach ($connections as $connection)<option value="{{ $connection->id }}">{{ $connection->display_label }}</option>@endforeach</select>
                    <select name="action" class="rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="{{ \App\Models\PluggyTransactionRule::ACTION_EXCLUDE }}">Não contabilizar nos totais</option>
                        <option value="{{ \App\Models\PluggyTransactionRule::ACTION_INTERNAL_TRANSFER }}">Transferência entre minhas contas</option>
                    </select>
                    <button class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700">Adicionar</button>
                </form>

                @error('phrase')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

                @if ($transactionRules->isNotEmpty())
                    <div class="mt-6 divide-y divide-slate-100 rounded-2xl border border-slate-100">
                        @foreach ($transactionRules as $rule)
                            <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-medium text-slate-800">“{{ $rule->phrase }}”</p>
                                    <p class="text-sm text-slate-500">{{ $rule->connection?->display_label ?? 'Todas as conexões (regra anterior)' }} @if($rule->ends_before) · Válida até {{ $rule->ends_before->copy()->subDay()->format('d/m/Y') }} @endif</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $rule->action === \App\Models\PluggyTransactionRule::ACTION_INTERNAL_TRANSFER ? 'Transferência própria: não entra como receita nem despesa.' : 'Excluída dos totais financeiros.' }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if (! $rule->ends_before)<details class="relative">
                                        <summary class="cursor-pointer list-none rounded-xl border border-blue-200 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-50">Editar</summary>
                                        <form method="POST" action="{{ route('pluggy.transaction-rules.update', $rule) }}" class="absolute right-0 z-10 mt-2 w-80 rounded-2xl border border-slate-200 bg-white p-4 shadow-xl">
                                            @csrf @method('PATCH')
                                            <label class="block text-xs font-medium text-slate-600">Frase</label>
                                            <input name="phrase" required minlength="3" maxlength="150" value="{{ $rule->phrase }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                            <label class="mt-3 block text-xs font-medium text-slate-600">Ação</label>
                                            <select name="action" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                                <option value="{{ \App\Models\PluggyTransactionRule::ACTION_EXCLUDE }}" @selected($rule->action === \App\Models\PluggyTransactionRule::ACTION_EXCLUDE)>Não contabilizar nos totais</option>
                                                <option value="{{ \App\Models\PluggyTransactionRule::ACTION_INTERNAL_TRANSFER }}" @selected($rule->action === \App\Models\PluggyTransactionRule::ACTION_INTERNAL_TRANSFER)>Transferência entre minhas contas</option>
                                            </select>
                                            <button class="mt-3 w-full rounded-xl bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">Salvar regra</button>
                                        </form>
                                    </details>
                                    <form method="POST" action="{{ route('pluggy.transaction-rules.destroy', $rule) }}" onsubmit="return confirm('Encerrar esta regra a partir da data escolhida e recalcular as movimentações?')">
                                        @csrf @method('DELETE')
                                        <label class="text-xs text-slate-600">Encerrar a partir de <input type="date" name="ends_before" required class="rounded-lg border-slate-200 text-sm"></label>
                                        <button class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Encerrar regra</button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
    <script>
        (() => {
            const overview = document.getElementById('connection-overview');
            const message = document.getElementById('sync-update-message');
            let timer;
            let stopped = false;
            let loading = false;

            function pending() {
                return [...overview.querySelectorAll('[data-sync-status]')].some(element =>
                    ['queued', 'running', 'awaiting_provider'].includes(element.dataset.syncStatus));
            }

            function schedule() {
                clearTimeout(timer);
                if (!stopped && !document.hidden) timer = setTimeout(refresh, pending() ? 5000 : 60000);
            }

            async function refresh() {
                if (loading || stopped || document.hidden) return;
                loading = true;
                try {
                    const response = await fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' });
                    if (!response.ok || response.redirected) throw new Error('Falha ao consultar estados');
                    const html = await response.text();
                    const next = new DOMParser().parseFromString(html, 'text/html').getElementById('connection-overview');
                    if (!next) throw new Error('Resposta inesperada');
                    if (stopped) return;
                    const wasPending = pending();
                    if (overview.innerHTML !== next.innerHTML) overview.innerHTML = next.innerHTML;
                    message.textContent = pending()
                        ? 'Acompanhando a sincronização. Alguns bancos podem levar mais tempo.'
                        : (wasPending ? 'Os estados foram atualizados. Confira o resultado de cada banco.' : '');
                } catch (error) {
                    message.textContent = 'Não foi possível atualizar os estados automaticamente. Verifique sua conexão ou atualize a página.';
                } finally {
                    loading = false;
                    schedule();
                }
            }

            document.addEventListener('submit', event => {
                if (!event.target.matches('[data-sync-form]')) return;
                stopped = true;
                clearTimeout(timer);
                const button = event.target.querySelector('button');
                if (button) { button.disabled = true; button.textContent = 'Solicitando...'; }
            });
            document.addEventListener('visibilitychange', () => document.hidden ? clearTimeout(timer) : refresh());
            if (pending()) message.textContent = 'Acompanhando a sincronização. Alguns bancos podem levar mais tempo.';
            schedule();
        })();
    </script>
</x-app-layout>
