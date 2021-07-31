<?php

namespace App\Http\Helpers;

use App\Models\Ptr;
use ErrorException;
use App\Models\Ticker;
use App\Models\TransactionType;
use App\Http\Helpers\EfdConnector;
use Illuminate\Support\Collection;

class PtrProcessor
{
    public $connector;
    private $ptrLinkIndex = 3;
    private $ptrTransactorFirstNameIndex = 0;
    private $ptrTransactorLastNameIndex = 1;
    private $ptrIdStartIndex = 26;
    private $ptrIdLength = 36;
    private $transactionDateIndex = 1;
    private $transactionOwnerIndex = 2;
    private $transactionTickerIndex = 3;
    private $transactionAssetNameIndex = 4;
    private $transactionAssetTypeIndex = 5;
    private $transactionTypeIndex = 6;
    private $transactionAmountIndex = 7;
    private $transactionCommentIndex = 8;

    public function __construct(EfdConnector $connector)
    {
        $this->connector = $connector;
    }

    public function processPtrIndex(\Generator $ptrIndex)
    {
        foreach($ptrIndex as $ptrPage) {
            $this->processPtrPage(collect($ptrPage->data));
        }
    }

    /** @todo Implement amendment and paper ptr functionality */
    private function processPtrPage(Collection $page)
    {
        [$electronicPtrs, $paperPtrs] = $this->partitionElectronicPaperPtrs($page);
        [$standardPtrs, $amendmentPtrs] = $this->partitionStandardAmendmentPtrs($electronicPtrs);
        
        $amendmentPtrs->each([$this, 'processAmendmentPtr']);
        $paperPtrs->each([$this, 'processPaperPtr']);

        $processedStandardPtrs = $this->processStandardPtrs($standardPtrs);

        $this->upsertPtrAttributes($processedStandardPtrs);
    }

    public function processStandardPtrs(Collection $ptrs)
    {
        return $ptrs->map([$this, 'processStandardPtr'])->filter([$this, 'filterOutPtrWithNoTransactions']);
    }

    public function processStandardPtr($ptr, $key)
    {
        $ptrLink = $ptr[$this->ptrLinkIndex];
        $ptrTransactorFirstName = $ptr[$this->ptrTransactorFirstNameIndex];
        $ptrTransactorLastName = $ptr[$this->ptrTransactorLastNameIndex];
        $ptrId = $this->parsePtrId($ptrLink);

        if (Ptr::find($ptrId)) {
            return;
        }

        $ptrHtml = $this->fetchPtrPage($ptrId);
        $ptrTransactions = collect($this->parsePtrHtml($ptrHtml));

        $stockTransactions = $ptrTransactions->whereIn('assetType', ['stock', 'stock option']);
        $blankTransactions = $ptrTransactions->where('assetType', '');

        $filledTransactions = $blankTransactions->filter([$this, 'filterOutBlankTicker'])->map(function($transaction, $key){
            $transaction['assetType'] = 'stock';
            return $transaction;
        });

        $processedStockTransactions = $this->processStockTransactions($stockTransactions->concat($filledTransactions), $ptrTransactorFirstName, $ptrTransactorLastName);

        return $processedStockTransactions;
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

    public function filterOutExchangeTransaction($transaction)
    {
        return $transaction['type'] !== 'exchange';
    }

    public function filterOutStockOptionTransaction($transaction)
    {
        return $transaction['assetType'] !== 'stock option';
    }

    public function filterOutPtrWithNoTransactions($ptr)
    {
        return $ptr->get('transactions')->count() > 0;
    }

    public function filterOutBlankTicker($transaction)
    {
        return $transaction['ticker'] !== '' && $transaction['ticker'] !== '--';
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
        return $this->connector->ptrShow($id);
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
                'date' => $tdvals[$this->transactionDateIndex],
                'owner' => strtolower($tdvals[$this->transactionOwnerIndex]),
                'ticker' => $tdvals[$this->transactionTickerIndex],
                'assetName' => $tdvals[$this->transactionAssetNameIndex],
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

    public function processExchangeTransactions(Collection $exchanges)
    {
        return $exchanges->map([$this, 'parseExchangeTickers']);
    }

    public function parseExchangeTickers($exchange, $key)
    {
        str_replace(' ', '', $exchange['ticker']);
        $exchange['ticker'] = preg_replace('/\s+/', '/', $exchange['ticker']);

        $explodedTicker = explode('/', $exchange['ticker']);
        $exchangedTicker = $explodedTicker[0];
        $receivedTicker = array_key_exists(1, $explodedTicker) ? $explodedTicker[1] : '--';

        $exchange['ticker'] = $exchangedTicker;
        $exchange['tickerReceived'] = $receivedTicker;

        trim($exchange['assetName']);
        $exchange['assetName'] = preg_replace('/\n+\s+/', '/', $exchange['assetName']);

        $explodedAssetName = explode('/', $exchange['assetName']);
        $exchangedAssetName = $explodedAssetName[0];
        $receivedAssetName = array_key_exists(1, $explodedAssetName) ? $explodedAssetName[1] : '--';

        $exchange['assetName'] = substr($exchangedAssetName, 0, strlen($exchangedAssetName) - 12);
        $exchange['assetNameReceived'] = substr($receivedAssetName, 0, strlen($receivedAssetName) - 11);

        return $exchange;
    }

    public function processStockOptionTransactions(Collection $transactions)
    {
        return $transactions->map([$this, 'parseStockOptionTicker']);
    }

    public function parseStockOptionTicker($transaction, $key)
    {
        $transaction['assetName'] = trim($transaction['assetName']);
        $transaction['assetName'] = explode("\n", $transaction['assetName'])[0];

        return $transaction;
    }

    public function parseProcessedPtrTickers()
    {

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

    public function pluckAndFlattenPtrTransactors(Collection $ptrs)
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

    public function findOrCreateTickerFromName($name)
    {
        $ticker = Ticker::where('name', $name)->first();

        if (!$ticker) {
            $ticker = new Ticker();
            $blankCount = Ticker::where('symbol', 'LIKE', '--%')->count();
            $ticker->symbol = '--' . ($blankCount + 1);
            $ticker->name = $name;
            $ticker->save();
        }

        return $ticker->symbol;
    }
}