<div x-data="{ show: false }" class="mb-2 relative z-0 w-4 h-4 order-2 lg:order-1">
    <div
        @click="show = true"
        class="absolute z-0 rounded-full w-4 h-4 cursor-pointer transition-transform transform hover:-translate-y-1" 
        style="background-color:#@stringtohex($transaction->ticker->symbol)"
    >
        <div
            x-cloak
            x-show="show"
            @click.away="show = false"
            class="absolute z-10 bottom-3/4 {{ $lastGroup ? 'right-3/4' : 'left-3/4' }} transform pl-2 pb-2 whitespace-nowrap"
        >
            <div class="absolute bottom-0 {{ $lastGroup ? 'right-1' : 'left-1' }} w-4 h-4 border-l-2 border-gray-600 transform {{ $lastGroup ? '-rotate-30' : 'rotate-30' }}" style="background: linear-gradient(to bottom right, #fff 0%, #fff 50%, transparent 50%)"></div>
            <div class="absolute -bottom-2 {{ $lastGroup ? 'right-1' : 'left-1' }} w-4 h-4 border-l-2 border-gray-600 transform {{ $lastGroup ? '-rotate-70' : 'rotate-70' }}"></div>
                

            <div class="relative z-20 border-2 border-gray-600 rounded-lg bg-white text-gray-700 p-4 cursor-auto">
                <div class="absolute w-1 h-1 -bottom-px {{ $lastGroup ? '-right-px' : '-left-px' }} bg-white"></div>
                @if (isset($showDate))
                    <p>{{ $transaction->transaction_date }}</p>
                @endif
                <p style="color:#@stringtohex($transaction->ticker->symbol)">
                    <a class="hover:underline" href="{{ route('ticker.show', $transaction->ticker->slug) }}" >{{ $transaction->ticker->symbol }}</a>
                </p>
                <p class="text-{{ $transaction->transactor->party['symbol'] }}">
                    <a class="hover:underline" href="{{ route('senator.show', $transaction->transactor->slug) }}" >{{ $transaction->transactor->shortName() }}</a>
                </p>
                <p>Owner: {{ Str::title($transaction->transaction_owner) }}</p>
                <p>{{ Str::title($transaction->transactionType->name) }}</p>
                <p>
                    ${{ number_format($transaction->transaction_amount_min) }} -<br>
                    ${{ number_format($transaction->transaction_amount_max) }}
                </p>
            </div>
        </div>
    </div>
</div>