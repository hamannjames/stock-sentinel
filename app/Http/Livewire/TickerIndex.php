<?php

namespace App\Http\Livewire;

use App\Models\Ticker;
use Livewire\Component;
use App\Models\Transaction;
use Livewire\WithPagination;

// this is identical to the senator index component, except with tickers it is safe to simply
// grab all of them from DB. Also I do not do any filtering by in office
class TickerIndex extends Component
{
    use WithPagination;

    public $search;

    protected $queryString = ['search' => ['except' => '']];

    public function mount()
    {
        $this->search = request()->search;
    }

    public function updatingSearch()
    {
        $this->resetErrorBag();
        $this->resetPage();
    }

    public function queryStringUpdatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $tickers = Ticker::when($this->search,
                fn($query) => $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('symbol', 'like', "%{$this->search}%")
            )
            ->where('symbol', 'not like', '%--%')
            ->orderBy('name')
            ->paginate(16);

        return view('livewire.ticker-index', ['tickers' => $tickers]);
    }
}
