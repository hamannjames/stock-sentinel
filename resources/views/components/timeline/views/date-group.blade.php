<x-timeline.views.dates>
    @php
        $transactionRange = range(0, $diff - 1, (int) $step);
    @endphp

    @foreach($transactionRange as $key=>$index)
        <div class="flex flex-grow flex-row md:flex-col justify-start md:justify-end items-center mx-2">
            @php
                $start = $startDate->toDateString();
                
                if ($startDate->diffInDays($endDate) <= $step) {
                    $startDate->addDays($startDate->diffInDays($endDate) + 1);
                }
                else {
                    $startDate->addDays($step - 1);
                }

                $end = $startDate->toDateString();
                $transactionGroup = $transactions->whereBetween('transaction_date', [$start, $end]);
            @endphp

            @if ($transactionGroup->isNotEmpty())
                @if ($transactionGroup->count() === 1)
                    <x-timeline.views.transaction-node showDate="1" :transaction="$transactionGroup->first()" :lastGroup="$loop->last" />
                @else
                    <x-timeline.views.transaction-group :startDate="$start" :endDate="$end" :transactionGroup="$transactionGroup" />
                @endif
            @endif

            <div class="order-1 md:order-2 text-center border-r-2 md:border-r-0 md:border-t-2 pr-2 md:pt-2 md:pr-0 mr-2 md:mt-2 md:mr-0">
                {{ \Carbon\Carbon::createFromFormat('Y-m-d', $start)->format('n/j') }} -<br>
                {{ \Carbon\Carbon::createFromFormat('Y-m-d', $end)->format('n/j') }}
            </div>

            @php
                $startDate->addDay();
            @endphp
        </div>
    @endforeach
</x-timeline.views.dates>