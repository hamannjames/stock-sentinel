<div>
    <button class="bg-action transition-colors hover:bg-action-light p-2 rounded-md" wire:click="{{ $connected ? 'disconnect' : 'connect' }}">
        @if ($connected)
            Untrack
        @else
            Track
        @endif
    </button>
</div>
