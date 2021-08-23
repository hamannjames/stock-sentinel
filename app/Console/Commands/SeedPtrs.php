<?php

namespace App\Console\Commands;

use App\Models\Ptr;
use App\Models\Ticker;
use App\Models\Transactor;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use App\Models\TransactionType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use App\Models\TransactionAssetType;
use App\Http\Helpers\Processors\PtrProcessor;

class SeedPtrs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:ptrs {--startDate=} {--endDate=} {--notify}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $nameDupeMap = collect([
            'Scott' => ['Tim', 'Rick'],
            'Johnson' => ['Tim', 'Ron'],
            'Udall' => ['Mark', 'Tom']
        ]);

        $nameDupeKeys = $nameDupeMap->keys();

        $processor = App::make(PtrProcessor::class);

        $this->info('Gathering Ptr Reports...');

        $data = $processor->connector->index([
            'startDate' => $this->option('startDate') ?: '01/01/2014',
            'endDate' => $this->option('endDate') ?: Carbon::now()->subDay()->format($processor->connector->getDateFormat())
        ]);

        $transactionTypes = TransactionType::all();
        $transactors = Transactor::all();
        $transactionAssetTypes = TransactionAssetType::all();

        $ptrTableCount = 0;
        $max = ceil((int)$data->current()->recordsTotal / $processor->connector->getPtrRequestLength());

        foreach($data as $table) {
            $ptrTableCount++;
            $this->line("Processing ptr table {$ptrTableCount} of {$max}");

            $ptrs = collect($table->data);
            [$electronicPtrs, $paperPtrs] = $processor->partitionElectronicPaperPtrs($ptrs);
            [$standardPtrs, $amendmentPtrs] = $processor->partitionStandardAmendmentPtrs($electronicPtrs);

            $this->info('--Fetching individual report data (takes a while)...');
            $bar = $this->output->createProgressBar($standardPtrs->count());
            $bar->start();

            $processedPtrs = $standardPtrs->map(function($ptr) use ($processor, $bar){
                $result = $processor->processDataRow($ptr);
                $bar->advance();
                return $result;
            })->filter([$processor, 'filterOutPtrWithNoTransactions']);
            $bar->finish();

            $this->newLine();

            $tickers = $processor->pluckAndDedupeTickers($processedPtrs)->filter(function($ticker){
                return $ticker['symbol'] !== '--';
            });

            $this->line('--Inserting tickers from ptrs');
            Ticker::upsert($tickers->all(), ['symbol'], ['name']);
            $tickers = Ticker::all();
            $ptrCount = 0;

            foreach ($processedPtrs->all() as $ptr) {
                $ptrCount++;
                $this->line("--Inserting ptr data {$ptrCount} of {$processedPtrs->count()}...");

                $ptrId = $ptr['id'];
                $lastName = $ptr['transactor']['lastName'];
                $transactorId;

                if ($nameDupeKeys->contains($lastName)) {
                    $firstName;

                    foreach($nameDupeMap[$lastName] as $first) {
                        if (strpos($ptr['transactor']['firstName'], $first)) {
                            $firstName = $first;
                            break;
                        }
                    }

                    $transactor = Transactor::where('first_name', $firstName)
                        ->where('last_name', $lastName)
                        ->first();

                    if (!$transactor) {
                        continue;
                    }

                    $transactorId = $transactor->id;
                }
                else {
                    $transactor = Transactor::where('last_name', $lastName)->first();
                    
                    if (!$transactor) {
                        continue;
                    }

                    $transactorId = $transactor->id;
                }

                $transactions = $ptr['transactions']->map(function($transaction) use ($ptrId, $transactorId, $tickers, $transactionAssetTypes, $transactionTypes){
                    return [
                        'transaction_date' => new \DateTime($transaction['date']),
                        'transactor_id' => $transactorId,
                        'ticker_id' => $this->findTickerId($transaction['ticker'], $tickers),
                        'ticker_received_id' => $this->findTickerReceivedId($transaction, $tickers),
                        'transactor_type_id' => 1,
                        'transaction_owner' => $transaction['owner'],
                        'transaction_type_id' => $this->findTransactionTypeId($transaction['type'], $transactionTypes),
                        'transaction_asset_type_id' => $this->findTransactionAssetTypeId($transaction['assetType'], $transactionAssetTypes),
                        'transaction_amount_min' => $this->findTransactionAmountMin($transaction['amount']),
                        'transaction_amount_max' => $this->findTransactionAmountMax($transaction['amount']),
                        'ptr_id' => $ptrId,
                        'ptr_row' => (int) $transaction['row']
                    ];
                });

                $this->line('----Inserting transactions');
                Transaction::upsert($transactions->all(), [
                    'ptr_id',
                    'ptr_row'
                ]);
                $this->line('----Inserting ptr uuid');
                Ptr::upsert(['uuid' => $ptrId], ['uuid']);
            }
        }
    }

    public function findTickerId($ticker, $tickers)
    {
        if ($ticker['symbol'] === '--' || $ticker['symbol'] === '') {
            return Ticker::handleBlankTicker($ticker['name'])->id;
        }

        return $tickers->where('symbol', $ticker['symbol'])->first()->id;
    }

    public function findTickerReceivedId($transaction, $tickers)
    {
        if ($transaction['type'] === 'exchange') {
            return $this->findTickerId($transaction['tickerReceived'], $tickers);
        }
    }

    public function findTransactionTypeId($name, $types)
    {
        return $types->where('name', $name)->first()->id;
    }

    public function findTransactionAssetTypeId($name, $assetTypes)
    {
        return $assetTypes->where('name', $name)->first()->id;
    }

    public function findTransactionAmountMin($amount)
    {
        $hyphenPos = strpos($amount, '-');
        $number = str_replace(',', '', substr($amount, 1, $hyphenPos - 1));
        
        return intval($number);
    }

    public function findTransactionAmountMax($amount)
    {
        $hyphenPos = strpos($amount, '-');
        $number = str_replace(',', '', substr($amount, $hyphenPos + 3));

        return intval($number);
    }
}
