<div class="relative mx-8 p-4 bg-gray-100 rounded-md text-black">
    <h2 class="px-2 mb-6 text-center text-2xl">{{ $title ?? 'Recent Transactions' }}</h2>
    <script>
        function writePtrLinkToClipboard(id, target) {
            navigator.clipboard.writeText('https://efdsearch.senate.gov/search/view/ptr/' + id + '/')
                .then(function(data){
                    target.classList.add('before:opacity-100', 'before:-translate-y-4');
                    setTimeout(function(){
                        target.classList.remove('before:opacity-100', 'before:-translate-y-4');
                    }, 700);
                });
        }
    </script>

    <section>
        <div class="flex text-sm">
            <label class="mr-4">
                View
                <div>
                    <select class="text-sm rounded-md border-green-400 border-2 text-black w-44" wire:model="view">
                        <option value="timeline">Timeline</option>
                        <option value="table">Table</option>
                    </select>
                </div>
            </label>
            <form wire:submit.prevent="newDates" class="flex space-x-4">
                <label>
                    Date Start
                    <div>
                        <input class="text-sm rounded-md border-2 border-green-400 text-black w-44" type="date" wire:model.defer="startDate">
                    </div>
                    @error('startDate') <div class="error text-red-400">{{ $message }}</div> @enderror
                </label>
                <label>
                    Date End
                    <div>
                        <input class="text-sm rounded-md border-2 border-green-400 text-black w-44" type="date" wire:model.defer="endDate">
                    </div>
                </label>
                <div class="self-end">
                    <input class="p-2 border-2 border-secondary bg-secondary hover:bg-action-light cursor-pointer transition-colors rounded-md leading-5" type="submit" value="Apply Dates">
                </div>
            </form>
        </div>
        <div class="flex mt-6 space-x-4">
            @foreach ($filterSettings as $filter => $settings)
                @php
                    $componentName = 'timeline.filters.' . Str::kebab(Str::camel($filter));    
                @endphp
                <x-dynamic-component :component="$componentName" classes="text-sm rounded-md border-2 border-accent-light text-black w-44" labelClasses="text-sm" :settings="$settings" />
            @endforeach
        </div>
    </section>
    
    @php
        $viewName = "timeline.views.{$view}";    
    @endphp

    <div wire:loading wire:target="newDates" class="my-4 text-lg">
        Fetching Transactions<span 
            x-data="{ count: 0 }" 
            x-init="setInterval(function(){
                count++;
                if (count > 3) {
                    count = 0;
                }
            }, 200)" 
            x-text="'.'.repeat(count)"></span>
    </div>

    <div wire:loading.delay wire:target="setDates" class="my-4 text-lg">
        Diving in<span 
            x-data="{ count: 0 }" 
            x-init="setInterval(function(){
                count++;
                if (count > 3) {
                    count = 0;
                }
            }, 200)" 
            x-text="'.'.repeat(count)"></span>
    </div>

    <div wire:loading wire:target="popDateHistory" class="my-4 text-lg">
        Heading Back<span 
            x-data="{ count: 0 }" 
            x-init="setInterval(function(){
                count++;
                if (count > 3) {
                    count = 0;
                }
            }, 200)" 
            x-text="'.'.repeat(count)"></span>
    </div>


    @if ($filteredTransactions->count())
        <x-dynamic-component :component="$viewName" :transactions="$filteredTransactions" :startDate="$startDate" :endDate="$endDate" :dateHistory="$dateHistory" />
    
        <x-timeline.views.transactionmetrics :startDate="$startDate" :endDate="$endDate" :filteredTransactions="$filteredTransactions" />
    @else
        <p>There were no transactions. Try adjusting your filters or setting a new date.</p>
    @endif
</div>
