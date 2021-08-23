<x-timeline.filters.filter label="Maximum Amount" :labelClasses="$labelClasses">
    <select class="{{ $classes ?? '' }}" wire:model="filters.amount_max">
        <option value="">Any</option>
        @foreach($settings as $amount)
            <option value="{{ $amount }}">${{ number_format($amount) }}</option>
        @endforeach
    </select>
</x-timeline.filters.filter>