@props(['id', 'title', 'number', 'description'])
<section id="{{ $id }}" class="finup-analysis-card" data-analysis-chart="{{ $id }}" aria-labelledby="{{ $id }}-title">
    <header>
        <div><span class="finup-analysis-number" aria-hidden="true">{{ $number }}</span><h2 id="{{ $id }}-title">{{ $title }}</h2></div>
        <p>{{ $description }}</p>
    </header>
    {{ $slot }}
</section>
