<section id="connect-bank" class="finup-connect-panel mb-6 rounded-2xl border border-slate-200 bg-white p-6">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm font-medium uppercase tracking-widest text-blue-600">Open Finance</p>
            <h2 class="mt-1 text-xl font-medium text-slate-900">Conecte suas contas com segurança</h2>
            <p class="mt-1 max-w-2xl text-sm text-slate-500">As contas e movimentações são sincronizadas pela Pluggy e ficam disponíveis nos seus totais.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <button type="button" id="btn-conectar-banco" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700">Conectar banco</button>
            <button type="button" id="btn-forcar-nova-conexao" class="hidden rounded-xl border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-medium text-amber-800 hover:bg-amber-100">Criar nova conexão</button>
            <a href="#connection-overview" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Minhas conexões</a>
        </div>
    </div>
    <div class="mt-5 flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row">
        <label for="pluggy-item-id" class="sr-only">Item ID do MeuPluggy</label>
        <input id="pluggy-item-id" type="text" class="min-w-0 flex-1 rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Já conectou no MeuPluggy? Cole o Item ID aqui">
        <button type="button" id="btn-vincular-item-pluggy" class="rounded-xl border border-blue-200 px-4 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50">Vincular Item existente</button>
    </div>
    <p id="pluggy-feedback" class="mt-3 hidden text-sm" role="status" aria-live="polite"></p>
</section>

<script src="https://cdn.pluggy.ai/pluggy-connect/v2.8.2/pluggy-connect.js"></script>

<script>
    async function registerPluggyItem(itemId) {
        const registrationResponse = await fetch('{{ route('pluggy.items.store') }}', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute('content')
            },
            body: JSON.stringify({ itemId })
        });

        const registrationData = await registrationResponse.json();

        if (!registrationResponse.ok) {
            throw new Error(registrationData.message || 'Não foi possível registrar a conexão.');
        }

        return registrationData;
    }

    function setPluggyFeedback(message, isError = false) {
        const feedback = document.getElementById('pluggy-feedback');
        feedback.textContent = message;
        feedback.className = `mt-3 text-sm ${isError ? 'text-red-600' : 'text-emerald-700'}`;
    }

    function shouldOfferNewConnection(error) {
        const code = error?.data?.codeDescription || error?.message || '';
        return String(code).includes('ITEM_USER_ALREADY_EXISTS');
    }

    async function openPluggyConnect(forceNew = false) {
        try {
            const response = await fetch('{{ route('pluggy.connect-token') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute('content')
                },
                body: JSON.stringify({ forceNew })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Erro ao gerar Connect Token.');
            }

            const pluggyConnect = new window.PluggyConnect({
                connectToken: data.accessToken,
                includeSandbox: @js(app()->environment('local')),

                onSuccess: async (itemData) => {
                    try {
                        const itemId = itemData?.item?.id || itemData?.itemId;
                        if (!itemId) {
                            throw new Error('A Pluggy não retornou o identificador da conexão.');
                        }
                        await registerPluggyItem(itemId);
                        document.getElementById('btn-forcar-nova-conexao').classList.add('hidden');
                        setPluggyFeedback('Banco conectado. A sincronização foi iniciada; os dados aparecerão em breve.');
                    } catch (error) {
                        setPluggyFeedback(error.message || 'O banco foi conectado, mas não foi possível registrar a sincronização.', true);
                    }
                },

                onError: (error) => {
                    if (shouldOfferNewConnection(error)) {
                        document.getElementById('btn-forcar-nova-conexao').classList.remove('hidden');
                        setPluggyFeedback('A Pluggy encontrou uma conexão antiga com estas credenciais. Você pode vincular o Item existente ou criar uma nova conexão explicitamente.', true);
                        return;
                    }

                    setPluggyFeedback('Não foi possível concluir a conexão com a instituição. Tente novamente ou revise a conexão.', true);
                }
            });

            pluggyConnect.init();
        } catch (error) {
            setPluggyFeedback(error.message || 'Não foi possível iniciar a conexão bancária.', true);
        }
    }

    document
        .getElementById('btn-vincular-item-pluggy')
        .addEventListener('click', async () => {
            const input = document.getElementById('pluggy-item-id');
            const itemId = input.value.trim();

            if (!itemId) {
                setPluggyFeedback('Informe o Item ID da sua conexão MeuPluggy.', true);
                return;
            }

            try {
                await registerPluggyItem(itemId);
                setPluggyFeedback('Conexão vinculada. A sincronização foi iniciada; acompanhe em Minhas conexões.');
                input.value = '';
            } catch (error) {
                setPluggyFeedback(error.message || 'Não foi possível vincular a conexão.', true);
            }
        });

    document
        .getElementById('btn-conectar-banco')
        .addEventListener('click', async () => {
            await openPluggyConnect();
        });

    document
        .getElementById('btn-forcar-nova-conexao')
        .addEventListener('click', async () => {
            await openPluggyConnect(true);
        });
</script>
