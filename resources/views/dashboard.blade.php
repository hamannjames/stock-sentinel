<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        @if ($connectionsCount) 
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <p class="mb-8">You have {{ $connectionsCount }} connections.</p>
                        <div class="flex space-x-8">
                            @if($connectedTransactors->isNotEmpty())
                                <div>
                                    <h3 class="text-xl">Tracking Senators</h3>
                                        @foreach($connectedTransactors as $transactor)
                                            <li><a class="text-{{ $transactor->party['symbol'] }}" href="{{ route('senator.show', $transactor->slug) }}">{{ $transactor->fullName() }}</a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if($connectedTickers->isNotEmpty())
                                <div>
                                    <h3 class="text-xl">Tracking Tickers</h3>
                                        @foreach($connectedTickers as $ticker)
                                            <li><a style="color:#@stringtohex($ticker->symbol)" href="{{ route('ticker.show', $ticker->slug) }}">{{ $ticker->symbol }} - {{ $ticker->name }}</a></li> 
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @if ($connectedTransactors->isNotEmpty())
                @php
                    $transactorPublicFilters = ['amount_min', 'amount_max', 'ticker', 'owner'];
                    $transactorPrivateFilters = ['transactor' => $connectedTransactors->pluck('id')->all()];
                @endphp
                <livewire:stock-transaction-timeline :transactions="$transactorTransactions" :startDate="$transactorStart" :endDate="$transactorEnd" :publicFilters="$transactorPublicFilters" :privateFilters="$transactorPrivateFilters" title="Senator Timeline" />
            @endif
            @if ($connectedTickers->isNotEmpty())
                @php
                    $tickerPublicFilters = ['amount_min', 'amount_max', 'transactor', 'owner'];
                    $tickerPrivateFilters = ['ticker' => $connectedTickers->pluck('id')->all()];
                @endphp
                <livewire:stock-transaction-timeline :transactions="$tickerTransactions" :startDate="$tickerStart" :endDate="$tickerEnd" :publicFilters="$tickerPublicFilters" :privateFilters="$tickerPrivateFilters" title="Ticker Timeline" />
            @endif
        @else
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <p>You have no connections. Get out there and start exploring! <a class="text-action hover:text-action-light" href="/">Start here</a>.</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
