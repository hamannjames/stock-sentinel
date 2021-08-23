<x-timeline.filters.filter label="Sitting Senators Only" :labelClasses="$labelClasses">
    <input class="{{ $classes ?? '' }}" style="width: 1rem" type="checkbox" wire:model="filters.in_office" />
</x-timeline.filters.filter>