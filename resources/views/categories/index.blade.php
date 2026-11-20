<x-app-layout>

<div class="finup-page finup-categories">
    <div class="finup-page-inner">

        {{-- Header --}}
        <div class="finup-page-heading">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-3">
                    <div>
                        <h1 class="text-3xl font-semibold text-white">Categorias</h1>
                        <p class="finup-page-intro">Gerencie suas categorias de receitas e despesas</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('category-groups.index') }}"
                       class="finup-button finup-button--secondary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        Gerenciar Grupos
                    </a>

                    <a href="{{ route('categories.create') }}"
                       class="finup-button">
                        <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Nova Categoria
                    </a>
                </div>
            </div>
        </div>

        {{-- Categorias por Tipo --}}
        <div class="finup-category-columns grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Receitas --}}
            <div class="finup-category-card overflow-hidden rounded-xl border border-neutral-600 bg-neutral-900 animate-slide-up" style="animation-delay: 0.1s">
                <div class="border-b border-green-700 bg-green-700 p-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-medium text-white">Receitas</h2>
                            <p class="text-sm text-green-100">{{ $categoriesIncome->count() }} categorias</p>
                        </div>
                    </div>
                </div>

                <div class="max-h-[600px] divide-y divide-neutral-700 overflow-y-auto">
                    @forelse($categoriesIncome as $category)
                        <div class="group p-4 transition-colors hover:bg-neutral-800">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="font-medium text-white">{{ $category->name }}</h3>
                                    @if($category->group)
                                        <p class="mt-1 text-xs text-green-400">
                                            <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                            </svg>
                                            {{ $category->group->name }}
                                        </p>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2">
                                    <a href="{{ route('categories.edit', $category) }}"
                                       class="rounded-lg p-2 text-blue-300 transition-colors hover:bg-neutral-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-300"
                                       title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('categories.destroy', $category) }}" method="POST"
                                          onsubmit="return confirm('Tem certeza que deseja excluir esta categoria?')"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="rounded-lg p-2 text-red-300 transition-colors hover:bg-neutral-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-300"
                                                title="Excluir">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-12 text-center">
                            <p class="text-slate-400 text-sm">Nenhuma categoria de receita</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Despesas --}}
            <div class="finup-category-card overflow-hidden rounded-xl border border-neutral-600 bg-neutral-900 animate-slide-up" style="animation-delay: 0.2s">
                <div class="border-b border-red-800 bg-red-800 p-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-rose-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-medium text-white">Despesas</h2>
                            <p class="text-sm text-red-100">{{ $categoriesExpense->count() }} categorias</p>
                        </div>
                    </div>
                </div>

                <div class="max-h-[600px] divide-y divide-neutral-700 overflow-y-auto">
                    @forelse($categoriesExpense as $category)
                        <div class="group p-4 transition-colors hover:bg-neutral-800">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="font-medium text-white">{{ $category->name }}</h3>
                                    @if($category->group)
                                        <p class="mt-1 text-xs text-red-400">
                                            <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                            </svg>
                                            {{ $category->group->name }}
                                        </p>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2">
                                    <a href="{{ route('categories.edit', $category) }}"
                                       class="rounded-lg p-2 text-blue-300 transition-colors hover:bg-neutral-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-300"
                                       title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('categories.destroy', $category) }}" method="POST"
                                          onsubmit="return confirm('Tem certeza que deseja excluir esta categoria?')"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="rounded-lg p-2 text-red-300 transition-colors hover:bg-neutral-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-300"
                                                title="Excluir">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-12 text-center">
                            <p class="text-slate-400 text-sm">Nenhuma categoria de despesa</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    * {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    @keyframes fade-in {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes slide-up {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .animate-fade-in { animation: fade-in 0.6s ease-out; }
    .animate-slide-up { animation: slide-up 0.8s ease-out backwards; }
</style>
</x-app-layout>
