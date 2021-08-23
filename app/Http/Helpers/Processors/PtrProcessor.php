<?php

namespace App\Http\Helpers\Processors;

use App\Models\Ptr;
use ErrorException;
use App\Models\Ticker;
use App\Models\TransactionType;
use Illuminate\Support\Collection;
use App\Http\Helpers\Connectors\EfdConnector;
use App\Http\Helpers\Processors\EfdTransactionProcessor;

// This processor is a specific implementation for efd data. 
class PtrProcessor extends ApiDataProcessor
{
    // most of these will be constant and only help map how the data should look for html scraping
    public $connector;
    protected $etp;
    private $ptrLinkIndex = 3;
    private $ptrTransactorFirstNameIndex = 0;
    private $ptrTransactorLastNameIndex = 1;
    private $ptrIdStartIndex = 26;
    private $ptrIdLength = 36;
    private $transactionRowIndex = 0;
    private $transactionDateIndex = 1;
    private $transactionOwnerIndex = 2;
    private $transactionTickerIndex = 3;
    private $transactionAssetNameIndex = 4;
    private $transactionAssetTypeIndex = 5;
    private $transactionTypeIndex = 6;
    private $transactionAmountIndex = 7;
    private $transactionCommentIndex = 8;

    public function __construct(EfdConnector $connector, EfdTransactionProcessor $transactionProcessor)
    {
        // the etp is the processor for single ptr data after it is fetched
        parent::__construct($connector);
        $this->etp = $transactionProcessor;
    }

    /** @todo Implement amendment and paper ptr functionality */
    public function processDataTable(Iterable $table)
    {
        if (!is_a($table, Collection::class)) {
            $table = collect($table);
        }

        // we first separate out all non standard ptrs
        [$electronicPtrs, $paperPtrs] = $this->partitionElectronicPaperPtrs($table);
        [$standardPtrs, $amendmentPtrs] = $this->partitionStandardAmendmentPtrs($electronicPtrs);
        
        //$amendmentPtrs->each([$this, 'processAmendmentPtr']);
        //$paperPtrs->each([$this, 'processPaperPtr']);
        return $this->processStandardPtrs($standardPtrs);
    }

    public function processStandardPtrs(Collection $ptrs)
    {
        // after processing every row, prune out those with no transactions
        $processedPtrs = $ptrs->map([$this, 'processDataRow'])->filter([$this, 'filterOutPtrWithNoTransactions']);

        // pluck all tickers which is helpful in case we want to mass upsert them
        $tickers = $this->pluckAndDedupeTickers($processedPtrs)->filter(function($ticker){
            return $ticker['symbol'] !== '--';
        });

        return [
            'tickers' => $tickers,
            'ptrs' => $processedPtrs
        ];
    }

    public function processDataRow(Iterable $row)
    {
        // grab ptr link, transactor name, and id. This is only the first part of the data returned
        // by the connector. We will be diving in to each ptr via it's link, which required an http
        // request and html scraping
        $ptrLink = $row[$this->ptrLinkIndex];
        $ptrTransactorFirstName = trim($row[$this->ptrTransactorFirstNameIndex]);
        $ptrTransactorLastName = trim($row[$this->ptrTransactorLastNameIndex]);
        $ptrId = $this->parsePtrId($ptrLink);
        $processedPtrTransactions = $this->processPtrReport($ptrId);

        // weird but necessary for pruning the name. sometimes names are formatted weird in api response.
        $blankSpacePos = strpos($ptrTransactorLastName, ' ');

        if ($blankSpacePos) {
            $ptrTransactorLastName = substr($ptrTransactorLastName, 0, $blankSpacePos);
        }

        $commaPos = strpos($ptrTransactorLastName, ',');

        if ($commaPos) {
            $ptrTransactorLastName = substr($ptrTransactorLastName, 0, $commaPos);
        }

        return [
            'transactor' => [
                'firstName' => $ptrTransactorFirstName,
                'lastName' => $ptrTransactorLastName
            ],
            'id' => $ptrId,
            'transactions' => $processedPtrTransactions
        ];
    }

    public function processPtrReport($id)
    {
        // get html for page
        $html = $this->fetchPtrPage($id);
        // parse html and turn array into collection
        $transactions = collect($this->parsePtrHtml($html));
        
        // pass off transactions parsed from html to be processed by etp class.
        return $this->etp->processDataTable($transactions);
    }

    public function pluckAndDedupeTickers(Collection $ptrs)
    {
        // this grabs all the tickers from each transaction in the ptrs and returns a unique set
        $tickers = $ptrs->pluck('transactions')
            ->map(function($transactions){
                return $transactions->reduce(function ($carry, $transaction) {
                    $carry[] = $transaction['ticker'];
                    return $carry;
                }, []);
            })
            ->flatten(1)
            ->unique('symbol');
        
        // we need to do the same for tickers received, which only show on exchange transactions
        $tickersReceived = $ptrs->pluck('transactions')
            ->map(function($transactions){
                return $transactions->reduce(function ($carry, $transaction) {
                    if (isset($transaction['tickerReceived'])) {
                        $carry[] = $transaction['tickerReceived'];
                    }

                    return $carry;
                }, []);
            })
            ->flatten(1)
            ->unique('symbol');

        return $tickers->concat($tickersReceived);
    }

    // not implemented yet
    public function processAmendmentPtr($ptr, $key) {}
    // not implemented yet
    public function processPaperPtr($ptr, $key) {}

    // we filter out paper ptrs simply be checking for paper in the link
    public function filterOutPaperPtr($ptrRow)
    {
        $ptrLink = $ptrRow[$this->ptrLinkIndex];
        return strpos($ptrLink, '/view/paper') == false;
    }

    // amendment ptrs will have amendment in the link
    public function filterOutAmendmentPtr($ptrRow)
    {
        $ptrLink = strtolower($ptrRow[$this->ptrLinkIndex]);
        return strpos($ptrLink, 'amendment') == false;
    }

    public function filterOutPtrWithNoTransactions(Iterable $ptr)
    {
        return count($ptr['transactions']) > 0;
    }

    public function partitionElectronicPaperPtrs(Collection $ptrs)
    {
        return $ptrs->partition([$this, 'filterOutPaperPtr']);
    }

    public function partitionStandardAmendmentPtrs(Collection $ptrs)
    {
        return $ptrs->partition([$this, 'filterOutAmendmentPtr']);
    }

    // simple method for extracting id from link
    public function parsePtrId($link)
    {
        return substr($link, $this->ptrIdStartIndex, $this->ptrIdLength);
    }

    public function getPtrLinkIndex()
    {
        return $this->ptrLinkIndex;
    }

    // alias for calling show method on connector
    public function fetchPtrPage($id)
    {
        return $this->connector->show($id);
    }

    public function parsePtrHtml($html)
    {
        // use DOMDocument class and xpath
        $doc = new \DOMDocument;
        $doc->preserveWhiteSpace = FALSE;
        // must do this for html5 spec
        libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        libxml_use_internal_errors(false);
        $xpath = new \DOMXpath($doc);
        // transactions live in tr tags
        $rows = $xpath->evaluate('//tbody//tr');

        $transactions;

        // scrape transactions from tr. Format of tds is assumed consistent.
        foreach ($rows as $tr)
        {
            $tdvals = [];

            foreach($xpath->query('td', $tr) as $td) {
                /* Skip the td with the empty text value */
                $tdvals[] = trim($td->nodeValue);  
            }

            // put data in friendly format. tickers get a symbol and name associative array
            $transactions[] = [
                'row' => $tdvals[$this->transactionRowIndex],
                'date' => $tdvals[$this->transactionDateIndex],
                'owner' => strtolower($tdvals[$this->transactionOwnerIndex]),
                'ticker' => [
                    'symbol' => $tdvals[$this->transactionTickerIndex],
                    'name' => $tdvals[$this->transactionAssetNameIndex]
                ],
                'assetType' => strtolower($tdvals[$this->transactionAssetTypeIndex]),
                'type' => strtolower($tdvals[$this->transactionTypeIndex]),
                'amount' => $tdvals[$this->transactionAmountIndex],
                'comment' => $tdvals[$this->transactionCommentIndex]
            ];
        }

        return $transactions;
    }

    // this functionality was moved to the etp class
    public function processStockTransactions(Collection $transactions, $transactorFirstName, $transactorLastName)
    {
        [$other, $exchanges] = $transactions->partition([$this, 'filterOutExchangeTransaction']);
        [$stocks, $stockOptions] = $other->partition([$this, 'filterOutStockOptionTransaction']);

        $processedExchanges = $this->processExchangeTransactions($exchanges);
        $processedStockOptions = $this->processStockOptionTransactions($stockOptions);

        $allProcessedTransactions = $stocks->concat($processedStockOptions)->concat($processedExchanges);
        $allProcessedTransactions->transform([$this, 'handleEmptyTicker']);

        $tickers = $allProcessedTransactions->pluck('ticker')
            ->merge($allProcessedTransactions->pluck('tickerReceived'))
            ->filter()
            ->unique();

        $tickersWithNames = $tickers->map(function($ticker, $key) use ($allProcessedTransactions){
            $matchedTicker = $allProcessedTransactions->where('ticker', $ticker)->first();

            if ($matchedTicker) {
                return [
                    'symbol' => $ticker,
                    'name' => $matchedTicker['assetName']
                ];
            }

            $matchedTicker = $allProcessedTransactions->where('tickerReceived', $ticker)->first();

            return [
                'symbol' => $ticker,
                'name' => $matchedTicker['assetNameReceived']
            ];
        });

        $types = $transactions->pluck('type')->unique();

        return collect([
            'transactor' => [
                'firstName' => $transactorFirstName,
                'lastName' =>$transactorLastName
            ],
            'tickers' => $tickersWithNames,
            'types' => $types,
            'transactions' => $allProcessedTransactions
        ]);
    }

    // this functionality was abandoned and is now handled by the calling script
    public function upsertPtrAttributes(Collection $processedPtrs)
    {
        $this->upsertPtrTickers($processedPtrs);
    }

    // this functionality was abandoned and is now handled by the calling script
    public function upsertPtrTickers(Collection $processedPtrs)
    {
        $tickers = $this->pluckAndFlattenPtrTickers($processedPtrs);
        Ticker::upsert($tickers->all(), ['symbol'], ['name']);

        return Ticker::all();
    }

    // no longer used
    public function pluckAndFlattenPtrTickers(Collection $ptrs)
    {
        return $ptrs->pluck('tickers')->flatten(1)->unique('symbol');
    }

    // this functionality was abandoned and is now handled by the calling script
    public function upsertPtrTransactionTypes(Collection $processedPtrs)
    {
        $types = $this->pluckAndFlattenPtrTransactionTypes($processedPtrs);
        TransactionType::upsert($types->all(), ['name']);

        return TransactionType::all();
    }

    // this functionality was abandoned and is now handled by the calling script
    public function pluckAndFlattenPtrTransactionTypes(Collection $ptrs)
    {
        return $ptrs->pluck('types')->flatten()->unique()->map(function($type, $key){
            return ['name' => $type];
        });
    }

    // this functionality was abandoned and is now handled by the calling script
    public function upsertPtrTransactors(Collection $processedPtrs)
    {
        $transactors = $this->pluckAndFlattenPtrTransactors($processedPtrs);
        Transactor::upsert($transactors->all(), ['firstName', 'lastName']);

        return Transactor::all();
    }

    // this functionality was abandoned and is now handled by the calling script
    public function pluckAndDedupeTransactors(Collection $ptrs)
    {
        return $ptrs->pluck('transactor')->unique(function($transactor){
            return $transactor['firstName'].$transactor['lastName'];
        });
    }

    // this functionality was abandoned and is now handled by Ticker model class
    public function handleEmptyTicker($transaction, $key)
    {
        if ($transaction['ticker'] === '--') {
            $transaction['ticker'] = $this->findOrCreateTickerFromName($transaction['assetName']);
        }

        if ($transaction['type'] === 'exchange' && $transaction['tickerReceived'] === '--') {
            $transaction['tickerReceived'] = $this->findOrCreateTickerFromName($transaction['assetNameReceived']);
        }

        return $transaction;
    }

    public function getEfdTransactionProcessor()
    {
        return $this->etp;
    }
}