<x-timeline.views.metrics.metrics title="Ticker Pie">
    @php
        $tickers = $transactions->pluck('ticker')->countBy('symbol')->sortDesc();
        $topTickers = $tickers->take(6);

        $gradientString = '';
        $percentSeen = 0;
        $tickersSeen = [];

        if ($topTickers->first() / $transactions->count() * 100 < 4) {
            $gradientString .= "#f0c224 0% 100%";
            $tickersSeen[] = 'Mixed';
        }
        else {
            foreach($topTickers as $symbol=>$count) {
                $percent = ($count / $transactions->count() * 100);
                if ($percent < 4) {
                    break;
                }

                if ($percentSeen > 0) {
                    $gradientString .= ', ';
                }

                $tickersSeen[] = $symbol;

                $hex = substr(md5($symbol), 0, 6);
                $percentTo = $percentSeen + $percent;
                $gradientString .= "#{$hex} 0% {$percentTo}%";
                $percentSeen = $percentTo;
            }

            if (count($tickersSeen) < $tickers->keys()->unique()->count()) {
                $tickersSeen[] = 'Mixed';
                $gradientString .= ", #f0c224 0% 100%";
            }
        }
    @endphp

    <div class="flex space-x-4">
        <div class="w-28 h-28 rounded-full" style="background:conic-gradient({{ $gradientString }})">
            
        </div>
        <div>
            <ul>
                @foreach($tickersSeen as $ticker)
                    @if ($ticker === 'Mixed')
                        <li>Mixed <div class="inline-block w-2 h-2 rounded-full bg-accent mb-1"></div></li>
                    @else
                        <li><a class="hover:underline" href="{{ route('ticker.show', Str::lower($ticker)) }}">{{ $ticker }}</a> <div class="inline-block w-2 h-2 rounded-full mb-1" style="background-color:#@stringtohex($ticker)"></div></li>
                    @endif
                @endforeach
            </li>
        </div>
    </div>
</x-timeline.views.metrics.metrics>