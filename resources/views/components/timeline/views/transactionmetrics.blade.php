<section class="mt-8 flex justify-center space-x-8 flex-wrap">
    <x-timeline.views.metrics.most-active :transactions="$filteredTransactions" />
    <x-timeline.views.metrics.tickerpie :transactions="$filteredTransactions" />
</section>