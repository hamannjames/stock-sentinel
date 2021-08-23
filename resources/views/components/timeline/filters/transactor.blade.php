<x-timeline.filters.filter label="Transactor" :labelClasses="$labelClasses">
    <select class="{{ $classes ?? '' }}" wire:model="filters.transactor">
        <option value="">Any</option>
        @foreach($settings as $transactor)
            <option value="{{ $transactor['id'] }}">{{ substr($transactor['first_name'], 0, 1) }}. {{ $transactor['last_name'] }}</option>
        @endforeach
    </select>
</x-timeline.filters.filter>