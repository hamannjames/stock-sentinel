<x-timeline.filters.filter label="Party" :labelClasses="$labelClasses">
    <select class="{{ $classes ?? '' }}" wire:model="filters.party">
        <option value="">Any</option>
        @foreach($settings as $setting)
            <option value="{{ $setting['symbol'] }}">{{ $setting['name'] }}</option>
        @endforeach
    </select>
</x-timeline.filters.filter>