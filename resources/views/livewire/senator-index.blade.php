<section>
    <p class="mx-8 mb-8 text-center max-w-screen-lg mx-auto">
        Stock Sentinel currently hold data on {{ $senators->total() }} senators. Some senators may not be in office, but we hold on to transaction records reported by senators since 2012. The public record maintains transaction data for elected representatives up to 6 years after they leave office, but we aim to maintain a longer store of that data.
    </p>

    <div class="flex mx-8 mb-4 space-x-4">
        <label>
            Search Name
            <div>
                <input class="rounded-md border-2 border-accent text-black" type="text" wire:model.debounce.500ms="search" />
            </div>
        </label>
        <label>
            In Office
            <div>
                <input class="rounded-md border-2 border-accent" type="checkbox" wire:model="inOffice" />
            </div>
        </label>
    </div>
    @error('search') <div class="error text-red-400 px-8 mb-4">{{ $message }}</div> @enderror
    <div class="flex flex-wrap">
        @foreach ($senators as $senator)
            <livewire:senator-index-single :senator="$senator" :key="$senator->id" />
        @endforeach
    </div>
    <div class="mx-8">
        {{ $senators->links() }}
    </div>
</section>
