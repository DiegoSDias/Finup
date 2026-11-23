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
