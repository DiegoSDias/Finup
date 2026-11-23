<x-app-layout>
    <div class="finup-page finup-page--form">
        <div class="finup-page-inner">
            <header class="finup-page-heading">
                <x-finup-back :href="route('categories.index')" />
                <h1>Editar <span>categoria</span></h1>
                <p class="finup-page-intro">Atualize as informações da categoria.</p>
            </header>
            <div class="finup-panel finup-form-panel finup-form">
                <form action="{{ route('categories.update', $category) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('categories._form')
                    <div class="finup-form-actions">
                        <a href="{{ route('categories.index') }}" class="finup-button finup-button--secondary">Cancelar</a>
                        <button type="submit" class="finup-button">Atualizar Categoria</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
