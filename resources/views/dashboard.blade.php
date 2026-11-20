<x-app-layout>
    <div class="finup-screen finup-dashboard">
        <section class="finup-dashboard-hero relative overflow-hidden">
            <div class="finup-dashboard-copy relative z-10">
                <h1 class="font-bold uppercase text-white">
                    <span class="block">Controle</span>
                    <span class="block text-[#27ae60]">financeiro</span>
                </h1>
                <p class="finup-dashboard-description text-white">Comece agora a acompanhar suas finanças mensalmente.</p>
                <a href="{{ route('finances.index') }}" class="finup-dashboard-cta inline-flex items-center gap-4 rounded-xl bg-[#27ae60] text-neutral-950 transition-colors hover:bg-green-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-green-400">
                    Ver minhas finanças
                    <svg class="h-6 w-6" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6" /></svg>
                </a>
            </div>
            <nav aria-label="Acessos rápidos" class="finup-dashboard-shortcuts relative z-10 text-white">
                <a href="{{ route('finances.index') }}" class="hover:text-green-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-green-400">
                    <svg aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.1" viewBox="0 0 24 24"><path d="M2 2h20v20H2zM12 4v16m3.5-12.5c-.7-1-2-1.5-3.5-1.5-2 0-3.5 1-3.5 2.5s1.5 2.1 3.5 2.5 3.5 1 3.5 3-1.5 3-3.5 3c-1.5 0-3-.7-4-1.7" /></svg>
                    <span>Visão Financeira</span>
                </a>
                <a href="{{ route('categories.index') }}" class="hover:text-green-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-green-400">
                    <svg aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.1" viewBox="0 0 24 24"><path stroke-linejoin="round" d="m3 7 9-5 9 5-9 5-9-5Zm0 5 9 5 9-5M3 17l9 5 9-5" /></svg>
                    <span>Categorias</span>
                </a>
                <a href="{{ route('category-groups.index') }}" class="hover:text-green-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-green-400">
                    <svg aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.1" viewBox="0 0 24 24"><circle cx="6" cy="6" r="3.5" /><path d="M14 2.5h7v7h-7zM2.5 14h7v7h-7z" /><circle cx="17.5" cy="17.5" r="3.5" /></svg>
                    <span>Grupos</span>
                </a>
                <a href="{{ route('pluggy.connections') }}" class="hover:text-green-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-green-400">
                    <svg aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.1" viewBox="0 0 24 24"><rect x="1" y="4.5" width="22" height="15" rx=".5" /><path d="M1 9h22" /><circle cx="6" cy="15" r="1" fill="currentColor" stroke="none" /></svg>
                    <span>Conexões</span>
                </a>
            </nav>
            <img src="{{ asset('images/finup/discos.png') }}" alt="" class="finup-dashboard-discs pointer-events-none absolute max-w-none" fetchpriority="high">
        </section>
    </div>

<meta name="csrf-token" content="{{ csrf_token() }}">

<section class="mx-auto mb-10 max-w-7xl px-6 sm:px-8">
    <div class="rounded-3xl border border-blue-100 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-widest text-blue-600">Open Finance</p>
                <h2 class="mt-1 text-xl font-medium text-slate-900">Conecte suas contas com segurança</h2>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">As contas e movimentações são sincronizadas pela Pluggy e ficam disponíveis nos seus totais.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="button" id="btn-conectar-banco" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700">Conectar banco</button>
                <button type="button" id="btn-forcar-nova-conexao" class="hidden rounded-xl border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-medium text-amber-800 hover:bg-amber-100">Criar nova conexão</button>
                <a href="{{ route('pluggy.connections') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Minhas conexões</a>
            </div>
        </div>
        <div class="mt-5 flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row">
            <label for="pluggy-item-id" class="sr-only">Item ID do MeuPluggy</label>
            <input id="pluggy-item-id" type="text" class="min-w-0 flex-1 rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Já conectou no MeuPluggy? Cole o Item ID aqui">
            <button type="button" id="btn-vincular-item-pluggy" class="rounded-xl border border-blue-200 px-4 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50">Vincular Item existente</button>
        </div>
        <p id="pluggy-feedback" class="mt-3 hidden text-sm" role="status" aria-live="polite"></p>
    </div>
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

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* Crop the white margins in the source background. Shell overrides
           apply only while the Dashboard is present. */
        body:has(.finup-dashboard) {
            --finup-home-gutter: clamp(1.5rem, 4.3vw, 5rem);
        }

        body:has(.finup-dashboard) > .min-h-screen {
            background-color: #1d1d1d;
            background-image: linear-gradient(#0006, #0006), url('{{ asset('images/finup/background.png') }}');
            background-attachment: fixed;
            background-position: center, center 50.3%;
            background-size: 100% 100%, 100% 223.14%;
        }

        body:has(.finup-dashboard) main.finup-theme {
            background: transparent !important;
        }

        body:has(.finup-dashboard) > .min-h-screen > nav {
            background: transparent;
            border: 0;
        }

        body:has(.finup-dashboard) > .min-h-screen > nav > div:first-child {
            max-width: none;
            padding-inline: var(--finup-home-gutter);
        }

        .finup-dashboard { background: transparent; }

        .finup-dashboard-hero {
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 2.5rem var(--finup-home-gutter);
        }

        .finup-dashboard-copy h1 {
            font-size: clamp(2.25rem, 4vw, 4rem);
            line-height: 1.2;
            letter-spacing: -.01em;
        }

        .finup-dashboard-description {
            max-width: 35rem;
            margin-top: 1.25rem;
            font-size: 1rem;
            line-height: 1.4;
        }

        .finup-dashboard-cta {
            margin-top: 1.5rem;
            min-height: var(--finup-control-height);
            padding: .625rem 1.125rem;
            font-size: .9375rem;
            line-height: 1.4;
        }

        .finup-dashboard-shortcuts {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.5rem 1rem;
            max-width: 40rem;
            margin-top: 3rem;
            font-size: .875rem;
        }

        .finup-dashboard-shortcuts a {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .625rem;
            border-radius: .25rem;
            text-align: center;
        }

        .finup-dashboard-shortcuts svg {
            width: 2rem;
            height: 2rem;
            color: #93dda9;
        }

        .finup-dashboard-discs { display: none; }

        @media (min-width: 640px) {
            .finup-dashboard-shortcuts {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .finup-dashboard-hero {
                min-height: max(34rem, calc(100svh - 5rem));
                padding: 4rem var(--finup-home-gutter) 9rem;
            }

            .finup-dashboard-copy { max-width: 60%; }
            .finup-dashboard-description {
                max-width: 32rem;
                font-size: 1.0625rem;
            }

            .finup-dashboard-shortcuts {
                position: absolute;
                bottom: 2rem;
                left: var(--finup-home-gutter);
                display: flex;
                gap: 2rem;
                max-width: none;
                margin-top: 0;
                font-size: .875rem;
                line-height: 1.3;
            }

            .finup-dashboard-shortcuts a {
                gap: .625rem;
            }

            .finup-dashboard-discs {
                display: block;
                top: .375rem;
                right: -14.2%;
                width: 71.5%;
            }
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

        @keyframes scale-in {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .animate-fade-in {
            animation: fade-in 0.6s ease-out;
        }

        .animate-slide-up {
            animation: slide-up 0.8s ease-out 0.2s backwards;
        }

        .animate-scale-in {
            animation: scale-in 0.4s ease-out backwards;
        }

        /* Custom Scrollbar */
        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
        }

        .scrollbar-thin::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</x-app-layout>
