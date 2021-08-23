<x-frontend-layout>
    <x-slot name="heading">{{ $ticker->symbol }}</x-slot>
    <x-slot name="subHeading">
        <span>{{ $ticker->name }}</span>
    </x-slot>

    <section>
        <p class="mx-8 text-center">
            {{ $ticker->name }} has been traded on in our records since {{ \Carbon\Carbon::createFromFormat('Y-m-d', $transactions->last()->transaction_date)->diffForHumans() }}. It's most recent transaction was {{ \Carbon\Carbon::createFromFormat('Y-m-d', $transactions->first()->transaction_date)->diffForHumans() }}.
        </p>
        @auth
            <div class="mb-8">
                <p
                    x-data="{ 
                        connected: {{ $connected ? 'true' : 'false' }},
                        html: '{{ $connected ? 'You are <span class="text-green-700">tracking</span> ' . $ticker->symbol : 'You are <span class="text-red-700">not tracking</span> ' . $ticker->symbol }}',
                        updateConnection(connect) {
                            if (connect) {
                                connected = true;
                                this.html = '{{ 'You are <span class="text-green-700">tracking</span> ' . $ticker->symbol }}'
                            }
                            else {
                                connected = false;
                                this.html = '{{ 'You are <span class="text-red-700">not tracking</span> ' . $ticker->symbol }}'
                            }
                        }
                    }"
                    @user-connected.window="updateConnection(true)"
                    @user-disconnected.window="updateConnection(false)"
                    class="mb-2 text-center"
                    x-html="html"
                >
                </p>
                <div class="text-center">
                    <livewire:connector-button :model="$ticker" :connected="$connected" />
                </div>
            </div>  
        @else
            <p class="text-center mb-8"><a class="underline text-action-dark transition-colors hover:text-action" href="{{ route('login') }}">Login</a> to track transactions for {{ $ticker->symbol }} and other stocks.</p>
        @endauth
    </section>

    <section>
        <h2 class="text-center text-2xl mb-4 mx-8">Transaction Data</h2>
        <div class="mb-4 mx-8 flex space-x-8 flex-wrap justify-center">
            <h3 class="text-xl">Total Transactions: {{ $transactions->count() }}</h3>
            @php
                $favoredSenatorId = $transactions->countBy('transactor.id')->sortDesc()->keys()->first();
                $favoredSenator = $transactions->where('transactor.id', $favoredSenatorId)->first()->transactor;
                $amountInvested = $ticker->amountInvested($transactions);
            @endphp
            <h3 class="text-xl">Most Popular With: <a class="text-{{ $favoredSenator->party['symbol'] }}" href="{{ route('senator.show', $favoredSenator->slug) }}">{{ $favoredSenator->fullName() }}</a></h3>
            <h3 class="text-xl">Estimated Amount Traded: ${{ number_format($amountInvested['min']) }} - ${{ number_format($amountInvested['max']) }}</h3>
        </div>

        @php
            $startDate = $transactions->last()->transaction_date;
            $endDate = $transactions->first()->transaction_date;

            $publicFilters = ['amount_min', 'amount_max', 'transactor'];
            $privateFilters = ['ticker' => [$ticker->id]];
        @endphp

        <livewire:stock-transaction-timeline :transactions="$transactions" :publicFilters="$publicFilters" :privateFilters="$privateFilters" :startDate="$startDate" :endDate="$endDate" title="Ticker Activity" />
    </section>
</x-frontend-layout>