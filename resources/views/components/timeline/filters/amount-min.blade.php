<x-timeline.filters.filter label="Minimum Amount" :labelClasses="$labelClasses">
    <select class="{{ $classes ?? '' }}" wire:model="filters.amount_min">
        @foreach($settings as $amount)
            <option value="{{ $amount }}">${{ number_format($amount) }}</option>
        @endforeach
    </select>
</x-timeline.filters.filter>