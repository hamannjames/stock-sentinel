<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Database\Eloquent\Collection;

class StockTransactionTimeline extends Component
{
    public $startDate;
    public $endDate;
    public $dateHistory = [];
    public $view;
    protected iterable $allowedFilters;
    public iterable $privateFilters;
    public iterable $filters;
    public iterable $publicFilters;
    public iterable $filterClasses;
    public iterable $filterSettings;
    public Collection $transactions;
    public Collection $filteredTransactions;
    public $title;

    protected function rules()
    {
        $endDate = Carbon::createFromFormat('Y-m-d', $this->endDate)->endOfDay();
        return [
            'startDate' => ['required', 'string', function($attr, $value, $fail) use ($endDate){
                if (Carbon::createFromFormat('Y-m-d', $value) > $endDate) {
                    $fail('Start Date cannot be greater than End Date');
                }
            }]
        ];
    }

    public function mount(Collection $transactions, $startDate, $endDate, iterable $publicFilters = [], iterable $privateFilters = [])
    {
        $this->allowedFilters = collect([
            'transactor' => 'transactor',
            'party' => 'party',
            'ticker' => 'ticker',
            'amount_min' => 'amount_min',
            'amount_max' => 'amount_max',
            'owner' => 'owner',
            'in_office' => 'in_office',
            'date_range' => 'date_range'
        ]);

        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->transactions = $transactions;

        $this->publicFilters = collect($publicFilters)
            ->intersect($this->allowedFilters)
            ->mapWithKeys([$this, 'collectFilter']);

        $this->privateFilters = collect($privateFilters)
            ->intersectByKeys($this->allowedFilters);

        $this->filters = $this->publicFilters->merge($this->privateFilters);
        $this->setUpFilters();
        $this->view = 'timeline';
    }

    public function render()
    {
        $state = $this->filters
            ->collect()
            ->put('models', $this->transactions);

        $this->filteredTransactions = app(Pipeline::class)
            ->send($state)
            ->through($this->filterClasses)
            ->thenReturn()['models'];

        return view('livewire.stock-transaction-timeline');
    }

    public function collectFilter($filterName, $key)
    {
        return [$filterName => null];
    }

    public function newDates()
    {
        $this->validate();

        $startDate = Carbon::createFromFormat('Y-m-d', $this->startDate);
        $endDate = Carbon::createFromFormat('Y-m-d', $this->endDate);

        $this->transactions = $this->queryTransactions($startDate, $endDate);
        $this->setUpFilters();
    }

    public function setDates($start, $end)
    {
        array_unshift($this->dateHistory, [$this->startDate, $this->endDate]);
        $this->startDate = $start;
        $this->endDate = $end;

        $this->privateFilters['date_range'] = [
            'startDate' => Carbon::createFromFormat('Y-m-d', $this->startDate)->startOfDay(),
            'endDate' => Carbon::createFromFormat('Y-m-d', $this->endDate)->endOfDay()
        ];

        $this->setPrivateFilters();
        $this->filterSettings = $this->createFilterSettings($this->publicFilters, $this->transactions->whereBetween(
            'transaction_date',
            [
                Carbon::createFromFormat('Y-m-d', $this->startDate)->startOfDay(),
                Carbon::createFromFormat('Y-m-d', $this->endDate)->endOfDay()
            ]
        ));
    }

    public function popDateHistory()
    {
        [$start, $end] = array_shift($this->dateHistory);
        $this->startDate = $start;
        $this->endDate = $end;

        if(empty($this->dateHistory)) {
            $this->privateFilters['date_range'] = [];
        }
        else {
            $this->privateFilters['date_range'] = [
                'startDate' => Carbon::createFromFormat('Y-m-d', $this->startDate)->startOfDay(),
                'endDate' => Carbon::createFromFormat('Y-m-d', $this->endDate)->endOfDay()
            ];
        }

        $this->setPrivateFilters();
        $this->filterSettings = $this->createFilterSettings($this->publicFilters, $this->transactions->whereBetween(
            'transaction_date',
            [
                Carbon::createFromFormat('Y-m-d', $this->startDate)->startOfDay(),
                Carbon::createFromFormat('Y-m-d', $this->endDate)->endOfDay()
            ]
        ));
    }

    private function queryTransactions($startDate, $endDate)
    {
        return Transaction::with('transactor')
            ->with('ticker')
            ->with('transactionType')
            ->when(array_key_exists('transactor', $this->privateFilters->all()),
                fn($query) => $query->whereIn('transactor_id', $this->filters['transactor'])
            )
            ->when(array_key_exists('transactor', $this->publicFilters->all()) && $this->publicFilters->get('transactor'),
                fn($query) => $query->where('transactor_id', $this->filters['transactor'])
            )
            ->when(array_key_exists('ticker', $this->privateFilters->all()),
                fn($query) => $query->whereIn('ticker_id', $this->filters['ticker'])
            )
            ->when(array_key_exists('ticker', $this->privateFilters->all()) && $this->publicFilters->get('ticker'),
                fn($query) => $query->where('ticker_id', $this->filters['ticker'])
            )
            ->whereBetween('transaction_date', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->orderBy('transaction_date')
            ->get();
    }

    private function setUpFilters()
    {
        $this->filterClasses = $this->createFilterClasses($this->filters);
        $this->filterSettings = $this->createFilterSettings($this->publicFilters, $this->transactions);
    }

    private function setPrivateFilters()
    {
        $this->filters = $this->filters->merge($this->privateFilters);
        $this->filterClasses = $this->createFilterClasses($this->filters);
    }

    private function createFilterClasses(iterable $filters)
    {
        return $filters
            ->keys()
            ->map(function($filterName){
                return $filterClassName = Str::replace('_', '', '\App\Models\Filters\TransactionFilters\\' . Str::title($filterName));
            })
            ->all();
    }

    private function createFilterSettings($filters, Collection $transactions)
    {
        return $filters->mapWithKeys(fn ($filter, $key) => call_user_func([$this, Str::camel($key) . 'Settings'], $key, $transactions));
    }

    private function inOfficeSettings($key, $transactions)
    {
        return [$key => ['0', '1']];
    }

    private function partySettings($key, $transactions)
    {
        return [$key => $transactions->pluck('transactor.party')->unique('symbol')->all()];
    }

    private function transactorSettings($key, $transactions)
    {
        return [$key => $transactions->pluck('transactor')->unique('id')];
    }

    private function tickerSettings($key, $transactions)
    {
        return [$key => $transactions->pluck('ticker')->unique('id')];
    }

    private function amountMinSettings($key, $transactions)
    {
        return [$key => [
            1,
            1001,
            15001,
            50001,
            100001,
            250001,
            500001,
            1000001,
            5000001,
            25000001
        ]];
    }

    private function amountMaxSettings($key, $transactions)
    {
        return [$key => [
            15000,
            50000,
            100000,
            250000,
            500000,
            1000000,
            5000000,
            25000000,
            50000000
        ]];
    }

    private function ownerSettings($key, $transactions)
    {
        return [$key => $transactions->pluck('transaction_owner')->unique()];
    }
}
