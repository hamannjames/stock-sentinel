<section class="mt-8">
    @php
        $carbonStart = \Carbon\Carbon::createFromFormat('Y-m-d', $startDate);
        $carbonEnd = \Carbon\Carbon::createFromFormat('Y-m-d', $endDate)->endofDay();
        $diff = $carbonStart->diffInDays($carbonEnd);
    @endphp
    
    @if (!empty($dateHistory))
        <button wire:click="popDateHistory" class="mb-4">&#60;&#60;Back</button>
    @endif

    @if ($diff <= 21)
        <x-timeline.views.days :startDate="$carbonStart" :endDate="$endDate" :diff="$diff" :transactions="$transactions" />
    @elseif ($diff <= 74)
        <x-timeline.views.date-group :startDate="$carbonStart" :endDate="$endDate" :diff="$diff" step="3" :transactions="$transactions" />
    @elseif ($diff <= 147)
        <x-timeline.views.date-group :startDate="$carbonStart" :endDate="$endDate" :diff="$diff" step="7" :transactions="$transactions" />
    @else
        @php
            $step = 7 * ceil($diff / 147); 
        @endphp
        <x-timeline.views.date-group :startDate="$carbonStart" :endDate="$endDate" :diff="$diff" :step="$step" :transactions="$transactions" />
    @endif
</section>