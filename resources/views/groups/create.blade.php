<x-app-layout>
    <div class="finup-page finup-page--form">
        <div class="finup-page-inner">
            <header class="finup-page-heading">
                <x-finup-back :href="route('category-groups.index')" />
                <h1>Novo <span>grupo</span></h1>
                <p class="finup-page-intro">Crie um grupo para organizar suas categorias.</p>
            </header>
            <div class="finup-panel finup-form-panel finup-form">
                <form action="{{ route('category-groups.store') }}" method="POST">
                    @csrf

                    @include('groups._form')
                    <div class="finup-form-actions">
                        <a href="{{ route('category-groups.index') }}" class="finup-button finup-button--secondary">Cancelar</a>
                        <button type="submit" class="finup-button">Criar Grupo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
