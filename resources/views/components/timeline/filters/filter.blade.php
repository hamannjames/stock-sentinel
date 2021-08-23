@props(['settings', 'label', 'labelClasses' => ''])
<div class="px-2">
    <label class="{{ $labelClasses }}">
        {{ $label }}
        <div>{{ $slot }}</div>
    </label>
</div>