<article class="finup-group-card {{ $expanded ? 'finup-group-detail' : '' }}">
    <header class="flex items-center justify-between gap-3">
        <h2>{{ $group->name }}</h2>
        <div class="flex items-center">
            <a href="{{ route('category-groups.edit', $group) }}" class="finup-icon-button" aria-label="Editar grupo {{ $group->name }}"><x-finup-icon name="edit" /></a>
            <form action="{{ route('category-groups.destroy', $group) }}" method="POST" onsubmit="return confirm('Tem certeza? Todas as categorias deste grupo serão desvinculadas.')">
                @csrf @method('DELETE')
                <button type="submit" class="finup-icon-button text-red-400" aria-label="Excluir grupo {{ $group->name }}"><x-finup-icon name="trash" /></button>
            </form>
        </div>
    </header>
    @if ($group->categories->isEmpty())
        <p class="mt-6 text-sm text-neutral-400">Nenhuma categoria neste grupo.</p>
    @else
        <ul>
            @foreach ($expanded ? $group->categories : $group->categories->take(4) as $category)
                <li><span class="{{ $category->type === 'income' ? 'bg-green-500' : 'bg-red-400' }}"></span><span>{{ $category->name }}</span></li>
            @endforeach
        </ul>
        @if (! $expanded && $group->categories->count() > 4)<p class="mt-2 text-sm text-neutral-400">+ {{ $group->categories->count() - 4 }} categorias</p>@endif
    @endif
    @unless ($expanded)
        <button type="button" class="finup-button" @click="selected = {{ (int) $group->id }}; $nextTick(() => $refs.groupView.focus())" aria-label="Ver mais categorias de {{ $group->name }}"><x-finup-icon name="eye" /> Ver mais</button>
    @endunless
</article>
