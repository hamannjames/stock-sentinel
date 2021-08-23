@props(['settings', 'label', 'labelClasses' => ''])
<div>
    <label class="{{ $labelClasses }}">
        {{ $label }}
        <div>{{ $slot }}</div>
    </label>
</div>