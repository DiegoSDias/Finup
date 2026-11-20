<x-app-layout>
    <div class="finup-page finup-page--form">
        <div class="finup-page-inner">
            <header class="finup-page-heading finup-movement-heading">
                <x-finup-back :href="url()->previous()" />
                <h1>Nova <span>{{ $type === 'income' ? 'receita' : 'despesa' }}</span></h1>
                <p class="finup-page-intro">Preencha os campos abaixo.</p>
            </header>
            <div class="finup-panel finup-form-panel finup-form">
                <form action="{{ route('expenses.store') }}" method="POST">
                    @csrf
                    @include('expenses._form')
                    <div class="finup-form-actions">
                        <a href="{{ url()->previous() }}" class="finup-button finup-button--secondary">Cancelar</a>
                        <button type="submit" @class(['finup-button', 'finup-button--danger' => $type === 'expense'])>Salvar {{ $type === 'income' ? 'receita' : 'despesa' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<script>
        const categorySelect = document.getElementById('categorySelect')
        const newCategoryField = document.getElementById('newCategoryField')
        const installmentRadios = document.querySelectorAll('input[name="is_installment"]')
        const installmentsField = document.getElementById('installmentsField')

        categorySelect.addEventListener('change', function() {
            if (this.value === 'new') {
                newCategoryField.classList.remove('hidden')
            } else {
                newCategoryField.classList.add('hidden')
            }
        })

        installmentRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === '1') {
                    installmentsField.classList.remove('hidden')
                } else {
                    installmentsField.classList.add('hidden')
                }
            })
        })
    </script>
</x-app-layout>
