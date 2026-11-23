<x-app-layout>

    @if (! $hasMovements)
        @include('months.empty')
    @else
        @include('expenses.index')
    @endif
</x-app-layout>
