@component('mail::message')
# New transaction!

A senator or ticker you track has a new transaction.

Date: {{ $transaction->transaction_date }}
Ticker: {{ $transaction->ticker->symbol }} - {{ $transaction->ticker->name }}
Senator: {{ $transaction->transactor->fullName() }}
Owner: {{ $transaction->transaction_owner }}
Type: {{ $transaction->transactionType->name }}
Amount: ${{ number_format($transaction->transaction_amount_min) }} - ${{ number_format($transaction->transaction_amount_max) }}

@component('mail::button', ['url' => route('senator.show', $transaction->transactor->slug)])
View Other Senator Activity
@endcomponent

@component('mail::button', ['url' => route('ticker.show', $transaction->ticker->slug)])
View Other Ticker Activity
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
