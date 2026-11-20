<div class="finup-month-empty min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 flex items-center justify-center p-8">
    
    <div class="max-w-6xl w-full">

        <a href="{{ route('finances.index', ['year' => $year]) }}" class="finup-back"><span aria-hidden="true">←</span> Voltar às finanças</a>

        
        
        {{-- Header --}}
        <div class="text-center my-12 animate-fade-in">
            <x-months-options :year="$year" :month="$month"/>
            
            <div class="mt-8">
                <h1 class="text-4xl font-light text-slate-900 mb-3">O que deseja adicionar?</h1>
                <p class="text-slate-500 text-lg">Escolha entre receita ou despesa</p>
            </div>
        </div>

        {{-- Cards de Escolha --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 animate-slide-up">
            
            {{-- Card Receita --}}
            <a href="{{ route('expenses.create', ['type' => 'income', 'year' => $year, 'month' => $month]) }}"
                class="group relative overflow-hidden bg-white rounded-3xl p-10 shadow-lg hover:shadow-2xl transition-all duration-500 border-2 border-transparent hover:border-emerald-200">
                
                {{-- Gradient Background --}}
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-50 to-green-50 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                
                {{-- Content --}}
                <div class="relative flex flex-col items-center text-center space-y-6">
                    
                    {{-- Icon --}}
                    <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-emerald-500 to-green-500 flex items-center justify-center shadow-lg group-hover:scale-110 group-hover:rotate-6 transition-all duration-500">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </div>
                    
                    {{-- Title --}}
                    <div>
                        <h2 class="text-3xl font-semibold text-slate-900 mb-2 group-hover:text-emerald-700 transition-colors">
                            Adicionar Receita
                        </h2>
                        <p class="text-slate-500 group-hover:text-emerald-600 transition-colors">
                            Registre entradas de dinheiro
                        </p>
                    </div>
                    
                    {{-- Examples --}}
                    <div class="flex flex-wrap gap-2 justify-center">
                        <span class="px-3 py-1.5 bg-emerald-100 text-emerald-700 rounded-lg text-sm font-medium">
                            Salário
                        </span>
                        <span class="px-3 py-1.5 bg-emerald-100 text-emerald-700 rounded-lg text-sm font-medium">
                            Presente
                        </span>
                        <span class="px-3 py-1.5 bg-emerald-100 text-emerald-700 rounded-lg text-sm font-medium">
                            Extra
                        </span>
                    </div>
                    
                    {{-- Arrow --}}
                    <div class="absolute bottom-8 right-8 opacity-0 group-hover:opacity-100 group-hover:translate-x-2 transition-all duration-500">
                        <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </div>
                    
                </div>
            </a>

            {{-- Card Despesa --}}
            <a href="{{ route('expenses.create', ['type' => 'expense', 'year' => $year, 'month' => $month]) }}"
                class="group relative overflow-hidden bg-white rounded-3xl p-10 shadow-lg hover:shadow-2xl transition-all duration-500 border-2 border-transparent hover:border-rose-200">
                
                {{-- Gradient Background --}}
                <div class="absolute inset-0 bg-gradient-to-br from-rose-50 to-red-50 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                
                {{-- Content --}}
                <div class="relative flex flex-col items-center text-center space-y-6">
                    
                    {{-- Icon --}}
                    <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-rose-500 to-red-500 flex items-center justify-center shadow-lg group-hover:scale-110 group-hover:rotate-6 transition-all duration-500">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                        </svg>
                    </div>
                    
                    {{-- Title --}}
                    <div>
                        <h2 class="text-3xl font-semibold text-slate-900 mb-2 group-hover:text-rose-700 transition-colors">
                            Adicionar Despesa
                        </h2>
                        <p class="text-slate-500 group-hover:text-rose-600 transition-colors">
                            Registre saídas de dinheiro
                        </p>
                    </div>
                    
                    {{-- Examples --}}
                    <div class="flex flex-wrap gap-2 justify-center">
                        <span class="px-3 py-1.5 bg-rose-100 text-rose-700 rounded-lg text-sm font-medium">
                            Comida
                        </span>
                        <span class="px-3 py-1.5 bg-rose-100 text-rose-700 rounded-lg text-sm font-medium">
                            Aluguel
                        </span>
                        <span class="px-3 py-1.5 bg-rose-100 text-rose-700 rounded-lg text-sm font-medium">
                            Transporte
                        </span>
                    </div>
                    
                    {{-- Arrow --}}
                    <div class="absolute bottom-8 right-8 opacity-0 group-hover:opacity-100 group-hover:translate-x-2 transition-all duration-500">
                        <svg class="w-8 h-8 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </div>
                    
                </div>
            </a>

        </div>

        {{-- Footer Info --}}
        <div class="mt-12 text-center animate-fade-in" style="animation-delay: 0.3s">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/80 backdrop-blur-sm rounded-xl border border-slate-200 text-sm text-slate-600">
                <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <span>Você pode cancelar a qualquer momento</span>
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

    .animate-fade-in {
        animation: fade-in 0.6s ease-out backwards;
    }

    .animate-slide-up {
        animation: slide-up 0.8s ease-out 0.2s backwards;
    }
</style>
