<div class="space-y-6">

    {{-- Nome --}}
    <div>
        <label for="name" class="block text-sm font-semibold text-slate-700 mb-2">
            Nome da Categoria
            <span class="text-red-500">*</span>
        </label>
        <input
            type="text"
            name="name"
            id="name"
            value="{{ old('name', $category->name ?? '') }}"
            class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm hover:border-slate-300"
            placeholder="Ex: Alimentação, Salário, Transporte..."
            required
        />
        @error('name')
            <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                {{ $message }}
            </p>
        @enderror
    </div>

    {{-- Tipo --}}
    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-3">
            Tipo
            <span class="text-red-500">*</span>
        </label>
        <div class="grid grid-cols-2 gap-3">
            <label class="relative flex items-center px-4 py-3 bg-white border-2 border-slate-200 rounded-xl cursor-pointer hover:border-emerald-300 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 transition-all group">
                <input
                    type="radio"
                    name="type"
                    value="income"
                    class="sr-only"
                    {{ old('type', $category->type ?? '') === 'income' ? 'checked' : '' }}
                    required
                >
                <div class="flex items-center gap-3">
                    <div class="w-5 h-5 rounded-full border-2 border-slate-300 group-has-[:checked]:border-emerald-500 group-has-[:checked]:bg-emerald-500 flex items-center justify-center transition-all">
                        <div class="w-2 h-2 bg-white rounded-full opacity-0 group-has-[:checked]:opacity-100 transition-opacity"></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                        </svg>
                        <span class="text-sm font-medium text-slate-700 group-has-[:checked]:text-emerald-700">Receita</span>
                    </div>
                </div>
            </label>

            <label class="relative flex items-center px-4 py-3 bg-white border-2 border-slate-200 rounded-xl cursor-pointer hover:border-rose-300 has-[:checked]:border-rose-500 has-[:checked]:bg-rose-50 transition-all group">
                <input
                    type="radio"
                    name="type"
                    value="expense"
                    class="sr-only"
                    {{ old('type', $category->type ?? '') === 'expense' ? 'checked' : '' }}
                    required
                >
                <div class="flex items-center gap-3">
                    <div class="w-5 h-5 rounded-full border-2 border-slate-300 group-has-[:checked]:border-rose-500 group-has-[:checked]:bg-rose-500 flex items-center justify-center transition-all">
                        <div class="w-2 h-2 bg-white rounded-full opacity-0 group-has-[:checked]:opacity-100 transition-opacity"></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                        </svg>
                        <span class="text-sm font-medium text-slate-700 group-has-[:checked]:text-rose-700">Despesa</span>
                    </div>
                </div>
            </label>
        </div>
        @error('type')
            <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                {{ $message }}
            </p>
        @enderror
    </div>

    {{-- Grupo --}}
    <div>
        <label for="group_id" class="block text-sm font-semibold text-slate-700 mb-2">
            Grupo (opcional)
        </label>
        <select
            name="group_id"
            id="group_id"
            class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm hover:border-slate-300 appearance-none cursor-pointer"
            style="background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%23475569%27 stroke-width=%272%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27%3e%3cpolyline points=%276 9 12 15 18 9%27%3e%3c/polyline%3e%3c/svg%3e'); background-repeat: no-repeat; background-position: right 1rem center; background-size: 1.25em;"
        >
            <option value="">Sem grupo</option>
                @foreach($groups as $group)
                    <option value="{{ $group->id }}" {{ old('group_id', $category->group_id ?? request('group_id')) == $group->id ? 'selected' : '' }}>
                        {{ $group->name }}
                    </option>
                @endforeach
        </select>
        <p class="mt-1.5 text-xs text-slate-500">
            Organize suas categorias em grupos para melhor visualização
        </p>
        @error('group_id')
            <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                {{ $message }}
            </p>
        @enderror
    </div>

    {{-- Dica sobre grupos --}}
    <div class="finup-form-tip bg-purple-50 border border-purple-200 rounded-xl p-4">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="text-sm text-purple-800">
                <p class="font-medium mb-1">Sobre Grupos:</p>
                <p>Grupos ajudam a organizar categorias similares. Por exemplo: "Fixas", "Variáveis", "Lazer".</p>
                <a href="{{ route('category-groups.index') }}" class="inline-flex items-center gap-1 mt-2 font-medium text-purple-700 hover:text-purple-900 transition-colors">
                    Gerenciar grupos
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

</div>
