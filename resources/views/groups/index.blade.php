<x-app-layout>
    <div class="finup-page finup-groups" x-data="{ selected: null }" x-ref="groupView" tabindex="-1">
        <div class="finup-page-inner">
            <div x-show="selected === null">
                <header class="finup-page-heading flex flex-wrap items-center justify-between gap-5">
                    <div><h1>Grupos das <span>categorias</span></h1><p class="finup-page-intro">Organize suas categorias em grupos.</p></div>
                    <a href="{{ route('category-groups.create') }}" class="finup-button"><x-finup-icon /> Novo Grupo</a>
                </header>
                @if ($groups->isEmpty())
                    <div class="finup-panel p-12 text-center text-neutral-300">Nenhum grupo criado. Crie seu primeiro grupo de categorias.</div>
                @else
                    <div class="finup-group-grid">
                        @foreach ($groups as $group) @include('groups._card', ['expanded' => false]) @endforeach
                    </div>
                @endif
            </div>
            @foreach ($groups as $group)
                <section x-cloak x-show="selected === {{ (int) $group->id }}" aria-label="Categorias de {{ $group->name }}">
                    <header class="finup-page-heading">
                        <button type="button" class="finup-back mb-8" @click="selected = null; $nextTick(() => $refs.groupView.focus())">← Voltar aos grupos</button>
                        <h1>{{ $group->name }}</h1><p class="finup-page-intro">Veja todas as categorias do grupo escolhido.</p>
                    </header>
                    @include('groups._card', ['expanded' => true])
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
