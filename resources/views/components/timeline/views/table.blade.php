<section>    
    <p>Total: {{ $transactions->count() }}</p>

    <div class="flex my-2 space-x-1 mt-2">
        <div class="flex-1 font-bold">
            Date<br>
            <span class="text-xs">(Click to copy report link)</span>
        </div>
        <div class="font-bold w-2/12">
            Transactor<br>
            <span class="text-xs">(Click to view page)</span>
        </div>
        <div class="font-bold w-2/12">
            Ticker<br>
            <span class="text-xs">(Click to view page)</span>
        </div>
        <div class="font-bold w-2/12">
            Owner
        </div>
        <div class="font-bold w-2/12">
            Type
        </div>
        <div class="font-bold w-2/12">
            Amount
        </div>
    </div>
    @forelse ($transactions as $transaction)
        <div class="flex my-2 p-1 space-x-1 {{ $loop->odd ? 'bg-gray-100' : '' }}">
            <div class="flex-1">
                <a 
                    x-data={}
                    title="Copy PTR Report Link to Clipboard"
                    @click.prevent="writePtrLinkToClipboard('{{ $transaction->ptr_id }}', $event.target)"
                    class="relative cursor-pointer text-yellow-600 hover:text-yellow-400 hover:underline content-before content-after before:text-green-700 after:text-red-700 before:absolute after:absolute before:left-full after:right-0 before:transform after:transform before:transition-transform after:transition-transform before:duration-1000 after:duration-700 before:opacity-0 after:opacity-0"
                    tw-content-before="✅ Copied Link"
                    tw-content-after="❌ Could not copy link"
                    role="button"
                >
                    {{ $transaction->transaction_date }}
                </a>
            </div>
            <div class="w-2/12 text-{{ $transaction->transactor->party['symbol'] }}">
                <a href="{{ route('senator.show', $transaction->transactor->slug) }}">{{ $transaction->transactor->fullName() }}</a>
            </div>
            <div class="w-2/12" style="color: #@stringtohex($transaction->ticker->symbol)">
                <a href="{{ route('ticker.show', $transaction->ticker->slug) }}">{{ $transaction->ticker->symbol }}</a>
            </div>
            <div class="w-2/12">
                {{ Str::title($transaction->transaction_owner) }}
            </div>
            <div class="w-2/12">
                {{ Str::title($transaction->transactionType->name) }}
            </div>
            <div class="w-2/12">
                ${{ number_format($transaction->transaction_amount_min) }} - ${{ number_format($transaction->transaction_amount_max) }}
            </div>
        </div>
    @empty
        <p>There are no transactions matching that criteria. Try adjusting your filters or reset.</p>
    @endforelse
</section>