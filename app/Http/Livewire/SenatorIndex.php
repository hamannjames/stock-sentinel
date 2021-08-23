<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Transactor;
use App\Models\Transaction;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// this class handles pagination for senators
class SenatorIndex extends Component
{
    // pagination is a trait that livewire components can use
    use WithPagination;

    // set up filters
    public $search;
    public $inOffice;
    public $inTransactionTable;

    // set up a minimum 3 character ruler for search
    protected $rules = ['search' => 'min:3'];
    // this class can update the query string
    protected $queryString = ['search' => ['except' => '']];

    public function mount()
    {
        // if search is in the query string then grab it
        $this->search = request()->search;

        // I want to ensure I am only displaying senators we have transaction data for
        $this->inTransactionTable = DB::table('transactions')
            ->select('transactor_id')
            ->distinct()
            ->get()
            ->pluck('transactor_id')
            ->all();
    }

    // this resets errors and paginated page when search is updated
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
        $useSearch = false;

        // if search is valid, we can use search, otherwise I add an error to the page
        if ($this->search) {
            try {
                $this->validate();
                $useSearch = true;
            }
            catch(ValidationException $e) {
                $this->addError('search', 'Search must be at least 3 characters');
            }
        }

        // get all senators who we have transactions for and, if search is enabled, match criteria
        $senators = Transactor::whereIn('id', $this->inTransactionTable)
            ->when($this->inOffice,
                fn($query) => $query->where('in_office', 1)
            )
            ->when($useSearch, 
                fn ($query) => $query->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('middle_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
            )
            ->orderBy('last_name')
            ->paginate(16);

        return view('livewire.senator-index', ['senators' => $senators]);
    }
}
