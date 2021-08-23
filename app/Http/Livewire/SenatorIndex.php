<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Transactor;
use App\Models\Transaction;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SenatorIndex extends Component
{
    use WithPagination;

    public $search;
    public $inOffice;
    public $inTransactionTable;

    protected $rules = ['search' => 'min:3'];
    protected $queryString = ['search' => ['except' => '']];

    public function mount()
    {
        $this->search = request()->search;

        $this->inTransactionTable = DB::table('transactions')
            ->select('transactor_id')
            ->distinct()
            ->get()
            ->pluck('transactor_id')
            ->all();
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
        $useSearch = false;

        if ($this->search) {
            try {
                $this->validate();
                $useSearch = true;
            }
            catch(ValidationException $e) {
                $this->addError('search', 'Search must be at least 3 characters');
            }
        }

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
