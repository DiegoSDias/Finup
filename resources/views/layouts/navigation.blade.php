<nav x-data="{ open: false }" class="finup-navigation relative z-50 border-b border-neutral-800 bg-neutral-950 text-white">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-20 justify-between gap-4">
            <div class="flex min-w-0 items-center gap-8">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-1" aria-label="FINUP — início">
                        <span class="relative h-12 w-12 overflow-hidden" aria-hidden="true"><img src="{{ asset('images/finup/marca.png') }}" alt="" class="absolute h-full max-w-none" style="width:242.51%;left:-81.29%"></span>
                        <span class="relative h-6 w-28 overflow-hidden" aria-hidden="true"><img src="{{ asset('images/finup/logotipo.png') }}" alt="" class="absolute max-w-none" style="height:865.53%;width:154.91%;left:-26.99%;top:-515.49%"></span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden flex-wrap items-center gap-x-4 lg:flex">
                    <x-nav-link class="finup-nav-home" :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Início') }}
                    </x-nav-link>

                    <x-nav-link :href="route('finances.index')" :active="request()->routeIs('finances.*')" :aria-current="request()->routeIs('finances.*') ? 'page' : null">
                        {{ __('Finanças') }}
                    </x-nav-link>

                    <x-nav-link :href="route('categories.index')" :active="request()->routeIs('categories.*')">
                        {{ __('Categorias') }}
                    </x-nav-link>

                    <x-nav-link :href="route('category-groups.index')" :active="request()->routeIs('category-groups.*')">
                        {{ __('Grupos') }}
                    </x-nav-link>

                    <x-nav-link :href="route('accounts-payable.index')" :active="request()->routeIs('accounts-payable.*')">
                        {{ __('Contas a pagar') }}
                    </x-nav-link>

                    <x-nav-link :href="route('pluggy.connections')" :active="request()->routeIs('pluggy.connections*')">
                        {{ __('Conexões') }}
                    </x-nav-link>

                    <x-nav-link :href="route('pluggy.data-notice')" :active="request()->routeIs('pluggy.data-notice')">
                        {{ __('Dados importados') }}
                    </x-nav-link>
                </div>
            </div>


            <!-- Settings Dropdown -->
            <div class="hidden lg:flex lg:items-center">
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open"
                        class="flex items-center gap-3 rounded-xl border border-transparent px-4 py-2 text-sm font-medium text-white transition-colors hover:border-neutral-700 hover:bg-neutral-800">
                        <div
                            class="w-8 h-8 rounded-lg bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center">
                            <span class="text-sm font-semibold text-slate-700">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </span>
                        </div>
                        <div class="text-left">
                            <div class="font-medium text-white">{{ Auth::user()->name }}</div>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 transition-transform" :class="{ 'rotate-180': open }"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                        class="finup-account-menu absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-slate-200 py-2 z-50"
                        style="display: none;">

                        <div class="finup-account-summary px-4 py-3 border-b border-slate-100">
                            <p class="finup-account-label text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Conta</p>
                            <p class="finup-account-name text-sm font-medium text-slate-900 truncate">{{ Auth::user()->name }}</p>
                            <p class="finup-account-email text-xs text-slate-500 truncate">{{ Auth::user()->email }}</p>
                        </div>

                        <a href="{{ route('profile.edit') }}"
                            class="finup-account-item finup-account-profile flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Perfil
                        </a>

                        <div class="finup-account-divider border-t border-slate-100 my-2"></div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="finup-account-item finup-account-logout w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Sair
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Hamburger -->
            <div class="flex items-center lg:hidden">
                <button @click="open = !open" aria-label="Abrir menu" :aria-expanded="open"
                    class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{ 'block': open, 'hidden': !open }" class="hidden border-t border-slate-200 bg-slate-50 lg:hidden">
        <div class="px-4 py-3 space-y-1">
            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('dashboard') ? 'bg-blue-100 text-blue-700' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Início
            </a>
            <a href="{{ route('finances.index') }}" @if(request()->routeIs('finances.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium {{ request()->routeIs('finances.*') ? 'bg-blue-100 text-blue-700' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">Finanças</a>
            <a href="{{ route('categories.index') }}" class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium text-slate-600 hover:bg-white hover:text-slate-900">Categorias</a>
            <a href="{{ route('category-groups.index') }}" class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium text-slate-600 hover:bg-white hover:text-slate-900">Grupos</a>
            <a href="{{ route('pluggy.connections') }}"
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('pluggy.connections*') ? 'bg-blue-100 text-blue-700' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-1.657 1.79-3 4-3s4 1.343 4 3-1.79 3-4 3-4 1.343-4 3 1.79 3 4 3 4-1.343 4-3M4 7v10a2 2 0 002 2h8M4 7a2 2 0 012-2h8M4 7l8 5 4-2.5" />
                </svg>
                Conexões
            </a>
            <a href="{{ route('accounts-payable.index') }}"
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('accounts-payable.*') ? 'bg-blue-100 text-blue-700' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l2 2 4-4m5.6-2.6a9 9 0 11-12.7 0 9 9 0 0112.7 0z" /></svg>
                Contas a pagar
            </a>
            <a href="{{ route('pluggy.data-notice') }}"
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('pluggy.data-notice') ? 'bg-blue-100 text-blue-700' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M5.1 19h13.8c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.37 16c-.77 1.33.19 3 1.73 3z" />
                </svg>
                Dados importados
            </a>
        </div>

        <!-- Responsive Settings Options -->
        <div class="finup-account-mobile pt-4 pb-3 border-t border-slate-200 bg-white">
            <div class="px-4 mb-3">
                <div class="flex items-center gap-3">
                    <div
                        class="finup-account-avatar w-10 h-10 rounded-lg bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center">
                        <span class="text-sm font-semibold text-slate-700">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </span>
                    </div>
                    <div>
                        <div class="finup-account-name font-medium text-base text-slate-900">{{ Auth::user()->name }}</div>
                        <div class="finup-account-email text-sm text-slate-500">{{ Auth::user()->email }}</div>
                    </div>
                </div>
            </div>

            <div class="px-4 space-y-1">
                <a href="{{ route('profile.edit') }}"
                    class="finup-account-item finup-account-profile flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-slate-600 hover:bg-blue-50 hover:text-blue-700 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Perfil
                </a>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="finup-account-item finup-account-logout w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Sair
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    * {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }
</style>
