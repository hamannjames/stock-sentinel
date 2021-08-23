<?php

// This command seeds all of the transactions data via "ptrs" from the eFD website
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
    // optional start date, end date, and notify users parameter
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
        // This is an imperfect way to handle duplicate last names. I looked through all my data
        // and saw these duplicates. I will need to update this list every time there is a new
        // election
        $nameDupeMap = collect([
            'Scott' => ['Tim', 'Rick'],
            'Johnson' => ['Tim', 'Ron'],
            'Udall' => ['Mark', 'Tom']
        ]);

        $nameDupeKeys = $nameDupeMap->keys();

        // I instantiate a processor for the data from service container
        $processor = App::make(PtrProcessor::class);

        // log a message to the user
        $this->info('Gathering Ptr Reports...');

        // the processor has a connector, which I can pass the dates to to begin gathering the data
        // from the api. If no options are set, I pass in an arbitrary start date of when I want
        // to collect data, and also yesterday's date, to get the most recent info.
        $data = $processor->connector->index([
            'startDate' => $this->option('startDate') ?: '01/01/2014',
            'endDate' => $this->option('endDate') ?: Carbon::now()->subDay()->format($processor->connector->getDateFormat())
        ]);

        // Get all of my seeded transaction types, which I will use when choosing which transactions
        // to process
        $transactionTypes = TransactionType::all();
        // Get all existing transactors. This command does not create transactors (senators), it matched
        // to them.
        $transactors = Transactor::all();
        // Get all seeded allowed transaction types (purchase, sale, etcetera)
        $transactionAssetTypes = TransactionAssetType::all();

        // start a count to inform the user of progress
        $ptrTableCount = 0;
        // determine how many iterations we will be doing on the collected data via the total records and
        // connector's request length parameter (this handles paginated reports)
        $max = ceil((int)$data->current()->recordsTotal / $processor->connector->getPtrRequestLength());

        foreach($data as $table) {
            // increment count and inform user of progress
            $ptrTableCount++;
            $this->line("Processing ptr table {$ptrTableCount} of {$max}");

            // cast the data from array to Laravel Collection class, which has more handy methods.
            $ptrs = collect($table->data);
            // partition out electronic ptrs from paper and standard from amendment. I only handle standard
            // ptrs at this time
            [$electronicPtrs, $paperPtrs] = $processor->partitionElectronicPaperPtrs($ptrs);
            [$standardPtrs, $amendmentPtrs] = $processor->partitionStandardAmendmentPtrs($electronicPtrs);

            // inform the user we are going to fetch all the report data and start a progress bar
            $this->info('--Fetching individual report data (takes a while)...');
            $bar = $this->output->createProgressBar($standardPtrs->count());
            $bar->start();

            $processedPtrs = $standardPtrs->map(function($ptr) use ($processor, $bar){
                // the processor knows how to massage the data. Do it and advance the bar
                // The processor will use the connector to fetch the data so it takes a while
                $result = $processor->processDataRow($ptr);
                $bar->advance();
                return $result;
                // filter out rows with no processed transactions (not stock)
            })->filter([$processor, 'filterOutPtrWithNoTransactions']);
            $bar->finish();

            $this->newLine();

            // pull all the tickers from the processd ptrs. There could be hundreds, even thousands, so
            // I want to upsert them in one batch instead of for every transaction.
            $tickers = $processor->pluckAndDedupeTickers($processedPtrs)->filter(function($ticker){
                return $ticker['symbol'] !== '--';
            });

            $this->line('--Inserting tickers from ptrs');
            // This "upserts" the tickers. It created a new record if not found. The model handles
            // data sanitization
            Ticker::upsert($tickers->all(), ['symbol'], ['name']);
            // retrieve fresh tickers
            $tickers = Ticker::all();
            $ptrCount = 0;

            foreach ($processedPtrs->all() as $ptr) {
                $ptrCount++;
                $this->line("--Inserting ptr data {$ptrCount} of {$processedPtrs->count()}...");

                // reformat the data to be more DB friendly. This should be done by processor but
                // I don't have time to edit now.
                $ptrId = $ptr['id'];
                $lastName = $ptr['transactor']['lastName'];
                $transactorId;

                // handle last name dupes. Pull down correct senator from DB
                if ($nameDupeKeys->contains($lastName)) {
                    $firstName;

                    foreach($nameDupeMap[$lastName] as $first) {
                        if (strpos($ptr['transactor']['firstName'], $first)) {
                            $firstName = $first;
                            break;
                        }
                    }

                    // Model handles data sanitization
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

                // final massage of all transaction data. This is all needed to properly save
                // the transaction to DB
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
                // Upsert all transacitions in one batch
                Transaction::upsert($transactions->all(), [
                    'ptr_id',
                    'ptr_row'
                ]);
                $this->line('----Inserting ptr uuid');
                // Finally, upsert the ptr to know we have fully processed it.
                Ptr::upsert(['uuid' => $ptrId], ['uuid']);
            }
        }
    }

    public function findTickerId($ticker, $tickers)
    {
        // The ticker model will handle a blank ticker, which sometimes happens
        if ($ticker['symbol'] === '--' || $ticker['symbol'] === '') {
            return Ticker::handleBlankTicker($ticker['name'])->id;
        }

        // find the ticker from the retrieved tickers
        return $tickers->where('symbol', $ticker['symbol'])->first()->id;
    }

    public function findTickerReceivedId($transaction, $tickers)
    {
        // for exchange transactions we need to find the exchanged ticker
        if ($transaction['type'] === 'exchange') {
            return $this->findTickerId($transaction['tickerReceived'], $tickers);
        }
    }

    // make sure transaction type is allowed by finding it in the pulled types
    public function findTransactionTypeId($name, $types)
    {
        return $types->where('name', $name)->first()->id;
    }

    // make sure the asset type is allowed by finding it in the pulled types
    public function findTransactionAssetTypeId($name, $assetTypes)
    {
        return $assetTypes->where('name', $name)->first()->id;
    }

    // format the amount as a number. it comes combined string, so we need to split min from max
    public function findTransactionAmountMin($amount)
    {
        $hyphenPos = strpos($amount, '-');
        $number = str_replace(',', '', substr($amount, 1, $hyphenPos - 1));
        
        return intval($number);
    }

    // pull max from string and format the amount as a number 
    public function findTransactionAmountMax($amount)
    {
        $hyphenPos = strpos($amount, '-');
        $number = str_replace(',', '', substr($amount, $hyphenPos + 3));

        return intval($number);
    }
}
