<x-timeline.views.dates>
    @php
        $transactionGroup = $transactions->groupBy('transaction_date');
        $transactionRange = range(0, $diff);
    @endphp
    
    @foreach($transactionRange as $index)
        <div class="relative flex flex-grow flex-row md:flex-col justify-start md:justify-end items-center mx-2" style="z-index:{{ count($transactionRange) - $index }}">
            @php
                $theDate = $startDate->toDateString();
            @endphp

            @if ($transactionGroup->has($theDate))
                @foreach ($transactionGroup->get($theDate) as $transaction)
                    <x-timeline.views.transaction-node :transaction="$transaction" :lastGroup="$loop->parent->last" />
                @endforeach
            @endif

            <div class="order-1 md:order-2 text-center border-r-2 md:border-r-0 md:border-t-2 pr-2 md:pt-2 md:pr-0 mr-2 md:mt-2 md:mr-0">
                {{ \Carbon\Carbon::createFromFormat('Y-m-d', $theDate)->format('n/j') }}
            </div>

            @php
                $startDate->addDay();
            @endphp
        </div>
    @endforeach
</x-timeline.views.dates>