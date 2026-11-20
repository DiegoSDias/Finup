<x-app-layout>
    <div class="finup-page finup-page--form">
        <div class="finup-page-inner">
            <header class="finup-page-heading">
                <x-finup-back :href="route('categories.index')" />
                <h1>Criar nova <span>categoria</span></h1>
                <p class="finup-page-intro">Crie uma nova categoria para organizar suas transações.</p>
            </header>
            <div class="finup-panel finup-form-panel finup-form">
                <form action="{{ route('categories.store') }}" method="POST">
                    @csrf

                    @include('categories._form')
                    <div class="finup-form-actions">
                        <a href="{{ route('categories.index') }}" class="finup-button finup-button--secondary">Cancelar</a>
                        <button type="submit" class="finup-button">Adicionar Categoria</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
