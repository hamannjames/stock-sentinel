<section>
    <p class="mx-8 mb-8 text-center max-w-screen-lg mx-auto">
        Stock Sentinel currently hold data on {{ $tickers->total() }} tickers.
    </p>

    <div class="flex mx-8 mb-4 space-x-4">
        <label>
            Search Name
            <div>
                <input class="rounded-md border-2 border-accent text-black" type="text" wire:model.debounce.500ms="search" />
            </div>
        </label>
    </div>
    @error('search') <div class="error text-red-400 px-8 mb-4">{{ $message }}</div> @enderror
    <div class="flex flex-wrap">
        @foreach ($tickers as $ticker)
            <div class="w-1/4 px-8">
                <a href="{{ route('ticker.show', $ticker->slug) }}">
                    <div class="border-2 border-gray-500 p-4 mb-12 rounded-xl bg-gray-200 text-gray-900 w-full max-w-xl transform transition-transform hover:-translate-y-1 text-center">
                        <p class="text-xl" style="color:#@stringtohex($ticker->symbol)">
                            {{ $ticker->symbol }}
                        </p>
                        <p>
                            {{ $ticker->name }}
                        </p>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
    <div class="mx-8">
        {{ $tickers->links() }}
    </div>
</section>
