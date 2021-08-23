<?php

namespace App\Http\Helpers\Processors;

use App\Models\Ptr;
use ErrorException;
use App\Models\Ticker;
use App\Models\TransactionType;
use Illuminate\Support\Collection;
use App\Http\Helpers\Connectors\EfdConnector;
use App\Http\Helpers\Processors\EfdTransactionProcessor;

class PtrProcessor extends ApiDataProcessor
{
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
        parent::__construct($connector);
        $this->etp = $transactionProcessor;
    }

    /** @todo Implement amendment and paper ptr functionality */
    public function processDataTable(Iterable $table)
    {
        if (!is_a($table, Collection::class)) {
            $table = collect($table);
        }

        [$electronicPtrs, $paperPtrs] = $this->partitionElectronicPaperPtrs($table);
        [$standardPtrs, $amendmentPtrs] = $this->partitionStandardAmendmentPtrs($electronicPtrs);
        
        //$amendmentPtrs->each([$this, 'processAmendmentPtr']);
        //$paperPtrs->each([$this, 'processPaperPtr']);
        return $this->processStandardPtrs($standardPtrs);
    }

    public function processStandardPtrs(Collection $ptrs)
    {
        $processedPtrs = $ptrs->map([$this, 'processDataRow'])->filter([$this, 'filterOutPtrWithNoTransactions']);
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
        $ptrLink = $row[$this->ptrLinkIndex];
        $ptrTransactorFirstName = trim($row[$this->ptrTransactorFirstNameIndex]);
        $ptrTransactorLastName = trim($row[$this->ptrTransactorLastNameIndex]);
        $ptrId = $this->parsePtrId($ptrLink);
        $processedPtrTransactions = $this->processPtrReport($ptrId);

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
        $html = $this->fetchPtrPage($id);
        $transactions = collect($this->parsePtrHtml($html));
        
        return $this->etp->processDataTable($transactions);
    }

    public function pluckAndDedupeTickers(Collection $ptrs)
    {
        $tickers = $ptrs->pluck('transactions')
            ->map(function($transactions){
                return $transactions->reduce(function ($carry, $transaction) {
                    $carry[] = $transaction['ticker'];
                    return $carry;
                }, []);
            })
            ->flatten(1)
            ->unique('symbol');
        
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

    public function processAmendmentPtr($ptr, $key) {}

    public function processPaperPtr($ptr, $key) {}

    public function filterOutPaperPtr($ptrRow)
    {
        $ptrLink = $ptrRow[$this->ptrLinkIndex];
        return strpos($ptrLink, '/view/paper') == false;
    }

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

    public function parsePtrId($link)
    {
        return substr($link, $this->ptrIdStartIndex, $this->ptrIdLength);
    }

    public function getPtrLinkIndex()
    {
        return $this->ptrLinkIndex;
    }

    public function fetchPtrPage($id)
    {
        return $this->connector->show($id);
    }

    public function parsePtrHtml($html)
    {
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

    public function upsertPtrAttributes(Collection $processedPtrs)
    {
        $this->upsertPtrTickers($processedPtrs);
    }

    public function upsertPtrTickers(Collection $processedPtrs)
    {
        $tickers = $this->pluckAndFlattenPtrTickers($processedPtrs);
        Ticker::upsert($tickers->all(), ['symbol'], ['name']);

        return Ticker::all();
    }

    public function pluckAndFlattenPtrTickers(Collection $ptrs)
    {
        return $ptrs->pluck('tickers')->flatten(1)->unique('symbol');
    }

    public function upsertPtrTransactionTypes(Collection $processedPtrs)
    {
        $types = $this->pluckAndFlattenPtrTransactionTypes($processedPtrs);
        TransactionType::upsert($types->all(), ['name']);

        return TransactionType::all();
    }

    public function pluckAndFlattenPtrTransactionTypes(Collection $ptrs)
    {
        return $ptrs->pluck('types')->flatten()->unique()->map(function($type, $key){
            return ['name' => $type];
        });
    }

    public function upsertPtrTransactors(Collection $processedPtrs)
    {
        $transactors = $this->pluckAndFlattenPtrTransactors($processedPtrs);
        Transactor::upsert($transactors->all(), ['firstName', 'lastName']);

        return Transactor::all();
    }

    public function pluckAndDedupeTransactors(Collection $ptrs)
    {
        return $ptrs->pluck('transactor')->unique(function($transactor){
            return $transactor['firstName'].$transactor['lastName'];
        });
    }

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