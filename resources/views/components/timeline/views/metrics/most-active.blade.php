<x-timeline.views.metrics.metrics title="Most Active">
    @php
        $tickers = array_values($transactions->pluck('ticker')->countBy('symbol')->sortDesc()->reduce(function($carry, $item, $key){
           $stringed = (string) $item;
           
           if (!array_key_exists($stringed, $carry)) {
               $carry[$stringed] = [$key];
           }
           else {
               $carry[$stringed][] = $key;
           }

           return $carry;
        }, []));
        
        $transactors = array_values($transactions->pluck('transactor')->countBy('id')->sortDesc()->reduce(function($carry, $item, $key){
            $stringed = (string) $item;
           
           if (!array_key_exists($stringed, $carry)) {
               $carry[$stringed] = [$key];
           }
           else {
               $carry[$stringed][] = $key;
           }

           return $carry;
        }, []));

        $tickerList = (count($tickers) > 1 || count($tickers[0]) === 1);
        $transactorList = (count($transactors) > 1 || count($transactors[0]) === 1);
    @endphp

    <div>
        Tickers:
        @if ($tickerList)
            @foreach ($tickers[0] as $ticker)
                <a class="hover:underline" style="color:#@stringtohex($ticker)" href="{{ route('ticker.show', Str::lower($ticker)) }}">{{ $ticker }}</a>{{ !$loop->last ? ', ' : ''}}
            @endforeach
        @else
            <span class="text-accent">Mixed</span>
        @endif
    </div>
    <div>
        Transactors:
        @if ($transactorList)
            @foreach ($transactors[0] as $transactor)
                @php
                    $tWithName = $transactions->where('transactor.id', $transactor)->first()->transactor;
                    $middleName = $tWithName->middle_name ? " {$tWithName->middle_name} " : ' ';
                    $fullName = $tWithName->first_name . $middleName . $tWithName->last_name
                @endphp
                <a class="hover-underline text-{{ $tWithName->party['symbol'] }}" href="{{ route('senator.show', $tWithName->slug) }}">{{ $tWithName->shortName() }}</a>{{ !$loop->last ? ', ' : ''}}
            @endforeach
        @else
            <span class="text-accent">Mixed</span>
        @endif
    </div>
</x-timeline.views.metrics.metrics>