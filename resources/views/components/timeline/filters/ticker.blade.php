<x-timeline.filters.filter label="Ticker" :labelClasses="$labelClasses">
    <select class="{{ $classes ?? '' }}" wire:model="filters.ticker">
        <option value="">Any</option>
        @foreach($settings as $ticker)
            <option value="{{ $ticker['id'] }}">{{ $ticker['symbol'] }}</option>
        @endforeach
    </select>
</x-timeline.filters.filter>