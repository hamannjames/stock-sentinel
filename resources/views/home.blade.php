<x-frontend-layout>
    <x-slot name="heading">Welcome to Stock Sentinel</x-slot>
    <x-slot name="subHeading">See stock transactions made by our elected representatives</x-slot>

    @php
        $publicFilters = ['amount_max', 'party', 'owner', 'in_office'];
    @endphp

    <p class="text-center px-4 max-w-screen-lg mx-auto mb-6">
        Stock Sentinel aims to bring transparency to how our elected representatives operate in the marketplace. While this data is publicly available, it is often presented in a format terribly difficult to digest by the average citizen. Stock Sentinel collects, analyzes, and presents these transactions in a way that is easy to understand and fun to navigate. 
    </p>

    <livewire:stock-transaction-timeline :transactions="$transactions" :publicFilters="$publicFilters" :startDate="$startDate" :endDate="$endDate" />
</x-frontend-layout>