<div x-data="{ show: false }" class="mb-2">
    <button wire:click="setDates('{{ $startDate }}', '{{ $endDate }}')" class="relative rounded-full w-7 h-7 cursor-pointer transition-transform transform hover:-translate-y-1 text-center" style="background-color: #@stringtohex($transactionGroup->first()->ticker->symbol)">
        <span class="align-middle">{{ $transactionGroup->count() }}</span>
    </button>
</div>