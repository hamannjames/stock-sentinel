<x-timeline.filters.filter label="Transaction Owner" :labelClasses="$labelClasses">
    <select class="{{ $classes ?? '' }}" wire:model="filters.owner">
        <option value="">Any</option>
        @foreach($settings as $setting)
            <option value="{{ $setting }}">{{ Str::title($setting) }}</option>
        @endforeach
    </select>
</x-timeline.filters.filter>