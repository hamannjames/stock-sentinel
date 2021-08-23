<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Database\Eloquent\Collection;

// this is the dooziest of my components. This is a highly reusable component for rendering
// transaction data and really the crux of the app at this time.
class StockTransactionTimeline extends Component
{
    // we need lots of properties to ensure the data looks right
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

    // these rules ensure the start date and end date are always in line
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

    // on first instantiation we perform some tasks
    public function mount(Collection $transactions, $startDate, $endDate, iterable $publicFilters = [], iterable $privateFilters = [])
    {
        // I have a list of allowed filters. The are keyed this way because public and private filters
        // are passed in differently and are compared to this array in different ways
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

        // ensure we have start date, end date, and transactions to display
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->transactions = $transactions;

        // public filters are passed in as an array of filter names. We compare to the values of the
        // allowed filters array. We then turn the result into an array with filters as keys and the 
        // initial value for each key blank
        $this->publicFilters = collect($publicFilters)
            ->intersect($this->allowedFilters)
            ->mapWithKeys([$this, 'collectFilter']);

        // private filters are passed in as associative array with values in place. We compare this by its
        // keys to the allowed filters array
        $this->privateFilters = collect($privateFilters)
            ->intersectByKeys($this->allowedFilters);

        // create a combined array filters list of public and private filters. only public filters are
        // shown to the user on the front end. This allows me to use private filters in interesting ways,
        // like passing in a senator id so only the senator's transactions are shown, which I do on
        // individual senator pages
        $this->filters = $this->publicFilters->merge($this->privateFilters);
        // perform some filter set up.
        $this->setUpFilters();
        // default view is timeline
        $this->view = 'timeline';
    }

    public function render()
    {
        // we create some state with filters and transactions
        $state = $this->filters
            ->collect()
            ->put('models', $this->transactions);

        // This is some laravel magic. Pipeline is a class which passes data, in this case state, through
        // an array of classes to filter, massage, or otherwise interact with the data. Laravel uses this
        // for middleware, but it can be used in all sorts of ways. In this case, I pass our intial
        // transactions through a host of filter classes depending on what public and private filters
        // we are using.
        $this->filteredTransactions = app(Pipeline::class)
            ->send($state)
            ->through($this->filterClasses)
            ->thenReturn()['models'];

        return view('livewire.stock-transaction-timeline');
    }

    // simply turn filter name into a key with null as default value
    public function collectFilter($filterName, $key)
    {
        return [$filterName => null];
    }

    // this is for when the user sets new dates
    public function newDates()
    {
        // validate dates
        $this->validate();

        // create start date and end date carbon isntances
        $startDate = Carbon::createFromFormat('Y-m-d', $this->startDate);
        $endDate = Carbon::createFromFormat('Y-m-d', $this->endDate);

        // we perform a brand new query to get brand new transactions for the class, and we set
        // up new filters
        $this->transactions = $this->queryTransactions($startDate, $endDate);
        $this->setUpFilters();
    }

    // this function is when a user clicks into a cluster of transactions with a certain date range
    /** @todo fix bug where transactions not showing on first set of dates */
    public function setDates($start, $end)
    {
        // we keep a date history for when the user clicks into the cluser, so we can go back
        array_unshift($this->dateHistory, [$this->startDate, $this->endDate]);
        $this->startDate = $start;
        $this->endDate = $end;

        // set a private filter of date range the user does not see
        // currently a bug where I need to subtract day and add day to end to get transactions in range
        $this->privateFilters['date_range'] = [
            'startDate' => Carbon::createFromFormat('Y-m-d', $this->startDate)->startOfDay()->subDay(),
            'endDate' => Carbon::createFromFormat('Y-m-d', $this->endDate)->endOfDay()->addDay()
        ];

        // call this to ensure our private filter class is part of the pipeline
        $this->setPrivateFilters();
        // create new filter settings based on transactions that match the date range. We only
        // want to show the user filter options that apply to the transactions seen in the UI
        $this->filterSettings = $this->createFilterSettings($this->publicFilters, $this->transactions->whereBetween(
            'transaction_date',
            [
                Carbon::createFromFormat('Y-m-d', $this->startDate)->startOfDay(),
                Carbon::createFromFormat('Y-m-d', $this->endDate)->endOfDay()
            ]
        ));
    }

    // this function is when a user clicks "back" when perusing the transaction clusters
    public function popDateHistory()
    {
        // remove the last date visited from the history
        [$start, $end] = array_shift($this->dateHistory);
        $this->startDate = $start;
        $this->endDate = $end;

        // is the history is now empty we can set the date range filter to an empty value
        // otherwise use the last date in history
        if(empty($this->dateHistory)) {
            $this->privateFilters['date_range'] = [];
        }
        else {
            $this->privateFilters['date_range'] = [
                'startDate' => Carbon::createFromFormat('Y-m-d', $this->startDate)->startOfDay(),
                'endDate' => Carbon::createFromFormat('Y-m-d', $this->endDate)->endOfDay()
            ];
        }

        // make sure private filter class is set up
        $this->setPrivateFilters();

        // as above, make sure the user is seeing filter options that apply to the current
        // transactions in UI
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
        // query all new transactions and if transactor or ticker are set as filters, we can
        // greatly cut down on our workload by ensuring that criteria is part of initial DB query
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
        // get classes for all the filters we are using
        $this->filterClasses = $this->createFilterClasses($this->filters);
        // get settings for all the public filters we are using that are shown to user
        $this->filterSettings = $this->createFilterSettings($this->publicFilters, $this->transactions);
    }

    private function setPrivateFilters()
    {
        // merge with private filters
        $this->filters = $this->filters->merge($this->privateFilters);
        //recreate filter classes
        $this->filterClasses = $this->createFilterClasses($this->filters);
    }

    private function createFilterClasses(iterable $filters)
    {
        // class name corresponds directly with directory structure. We don't actually create classes but
        // return the string name of class with namespace
        return $filters
            ->keys()
            ->map(function($filterName){
                return $filterClassName = Str::replace('_', '', '\App\Models\Filters\TransactionFilters\\' . Str::title($filterName));
            })
            ->all();
    }

    private function createFilterSettings($filters, Collection $transactions)
    {
        // this class has a function devoted specifically to each filter setting, and we call it here.
        // This to me is a lot cleaner than having a big if/else or switch
        return $filters->mapWithKeys(fn ($filter, $key) => call_user_func([$this, Str::camel($key) . 'Settings'], $key, $transactions));
    }

    // in office is a simepl boolean so represented by 0 and 1
    private function inOfficeSettings($key, $transactions)
    {
        return [$key => ['0', '1']];
    }

    // get all political parties represented in transactions
    private function partySettings($key, $transactions)
    {
        return [$key => $transactions->pluck('transactor.party')->unique('symbol')->all()];
    }

    // get all transactors represented in transactions
    /** @todo see what happens if I return array instead of collection */
    private function transactorSettings($key, $transactions)
    {
        return [$key => $transactions->pluck('transactor')->unique('id')];
    }

    // Get all tickers represented in transactions
    /** @todo see what happens if I return array instead of collection */
    private function tickerSettings($key, $transactions)
    {
        return [$key => $transactions->pluck('ticker')->unique('id')];
    }

    // somewhat arbitrary amounts. This is one of few filters where I do not care what is in
    // current transaction set
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

    // get all owners represented in transactions
    private function ownerSettings($key, $transactions)
    {
        return [$key => $transactions->pluck('transaction_owner')->unique()];
    }
}
