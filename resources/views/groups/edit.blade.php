<x-app-layout>
    <div class="finup-page finup-page--form">
        <div class="finup-page-inner">
            <header class="finup-page-heading">
                <x-finup-back :href="route('category-groups.index')" />
                <h1>Editar <span>grupo</span></h1>
                <p class="finup-page-intro">Atualize o grupo e suas categorias.</p>
            </header>
            <div class="finup-panel finup-form-panel finup-form">
                <form action="{{ route('category-groups.update', $group) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('groups._form')
                    <div class="finup-form-actions">
                        <a href="{{ route('category-groups.index') }}" class="finup-button finup-button--secondary">Cancelar</a>
                        <button type="submit" class="finup-button">Salvar Grupo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
