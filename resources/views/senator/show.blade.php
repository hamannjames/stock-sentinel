<x-frontend-layout>
    <x-slot name="heading">{{ $senator->fullName() }}</x-slot>
    <x-slot name="subHeading">
        <span>{{ $senator->party['name'] }}</span> - {{ $senator->state['name'] }}
    </x-slot>

    @if (!$transactions)
        <p class="mx-8 text-center">
            This senator is not active in our records. I guess they don't like the stock!
        </p>
    @else
        <section>
            <figure
                    x-data = "{ 
                        avatar: sessionStorage.getItem('avatar-{{$senator->id}}')
                    }"
                    x-init = "avatar = avatar || (function(){
                        const avatar = '{{ $senator->getRandomAvatar() }}'
                        sessionStorage.setItem('avatar-{{$senator->id}}', avatar)
                        return avatar
                    })()"
                    class="w-36 mb-4 block mx-auto"
                >
                    <img class="max-w-full rounded-full mx-auto" :src="avatar" alt="Profile Image" />
            </figure>
            <p class="mx-8 text-center">
                {{ $senator->fullName() }} has been active in our records since {{ \Carbon\Carbon::createFromFormat('Y-m-d', $transactions->last()->transaction_date)->diffForHumans() }}. Their most recent transaction was {{ \Carbon\Carbon::createFromFormat('Y-m-d', $transactions->first()->transaction_date)->diffForHumans() }}.
            </p>
            <script>
                function init() {
                    return {
                        text: 'Fetching Data',
                        counter: 0,
                        fullName: '{{ $senator->fullName() }}',
                        timer() {
                            this.counter += 1;
                            this.text += '.';
                            if (this.counter === 3) {
                                this.text = 'Fetching Data'
                            }
                        },
                        fetchData() {
                            setInterval(this.timer, 200);
                            fetch('{{ route('ProPublica.transactor.show', $senator->id) }}')
                                .then(res => res.json())
                                .then(res => {
                                    clearInterval(this.timer);
                                    this.text = `Learn more about ${this.fullName} at <a href="${res.url}">${res.url}</a>`
                                })
                        }
                    }
                }
            </script>
            <p
                x-data="init()"
                x-init="fetchData()"
                x-html="text"
                class="text-center mb-8"
            >
            </p>
            @auth
                <div class="mb-8">
                    <p
                        x-data="{ 
                            connected: {{ $connected ? 'true' : 'false' }},
                            html: '{{ $connected ? 'You are <span class="text-green-700">tracking</span> ' . $senator->fullName() : 'You are <span class="text-red-700">not tracking</span> ' . $senator->fullName() }}',
                            updateConnection(connect) {
                                if (connect) {
                                    connected = true;
                                    this.html = '{{ 'You are <span class="text-green-700">tracking</span> ' . $senator->fullName() }}'
                                }
                                else {
                                    connected = false;
                                    this.html = '{{ 'You are <span class="text-red-700">not tracking</span> ' . $senator->fullName() }}'
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
                        <livewire:connector-button :model="$senator" :connected="$connected" />
                    </div>
                </div>  
            @else
                <p class="text-center mb-8"><a class="underline text-action-dark transition-colors hover:text-action" href="{{ route('login') }}">Login</a> to track transaction data from {{ $senator->fullName() }} and other senators.</p>
            @endauth
        </section>

        <section>
            <h2 class="text-center text-2xl mb-4 mx-8">Transaction Data</h2>
            <div class="mb-4 mx-8 flex space-x-8 flex-wrap justify-center">
                <h3 class="text-xl">Total Transactions: {{ $transactions->count() }}</h3>
                @php
                    $favoredTickerSymbol = $transactions->countBy('ticker.symbol')->sortDesc()->keys()->first();
                @endphp
                <h3 class="text-xl">Favored Ticker: <a style="color:#@stringtohex($favoredTickerSymbol)" href="{{ route('ticker.show', Str::lower($favoredTickerSymbol)) }}">{{ $favoredTickerSymbol }}</a></h3>
                <h3 class="text-xl">Estimated Amount Invested: ${{ number_format($senator->amountInvested($transactions)['min']) }} - ${{ number_format($senator->amountInvested($transactions)['max']) }}</h3>
            </div>

            @php
                $startDate = $transactions->last()->transaction_date;
                $endDate = $transactions->first()->transaction_date;

                $publicFilters = ['amount_min', 'amount_max', 'ticker'];
                $privateFilters = ['transactor' => [$senator->id]];
            @endphp

            <livewire:stock-transaction-timeline :transactions="$transactions" :publicFilters="$publicFilters" :privateFilters="$privateFilters" :startDate="$startDate" :endDate="$endDate" title="Senator Activity" />
        </section>
    @endif
</x-frontend-layout>