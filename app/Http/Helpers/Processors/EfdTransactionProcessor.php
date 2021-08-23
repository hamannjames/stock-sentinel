<?php

namespace App\Http\Helpers\Processors;

use Illuminate\Support\Collection;
use App\Http\Helpers\Processors\DataProcessor;

// This class implements a standard data processor and is concerned with single ptr data
class EfdTransactionProcessor implements DataProcessor
{
    // for now I am storing allowable stock types as a property of the class.
    public $allowableStockTypes = ['stock', 'stock option'];

    // The process data table function weeds out not stock transactions, fills blank types with stock
    // if a ticker exists, then passes on to row processor
    public function processDataTable(Iterable $table) {
        if (!is_a($table, Collection::class)) {
            $table = collect($table);
        }

        $stockTransactions = $this->filterOutNonStockTransactions($table);
        $filledBlankTransactions = $this->fillBlankAssetTypesWithStock($table);

        return $stockTransactions->concat($filledBlankTransactions)->map([$this, 'processDataRow']);
    }

    // we handle exchange and stock option transactions a specific way
    public function processDataRow(Iterable $row) {
        if ($row['type'] === 'exchange') {
            return $this->processExchangeTransaction($row);
        }

        if ($row['assetType'] === 'stock option') {
            return $this->processStockOptionTransaction($row);
        }

        return $row;
    }

    // if the transactions is exchange, we need to parse out the exchanged ticker and received ticker
    public function processExchangeTransaction(Iterable $exchange)
    {
        $ticker = $exchange['ticker'];
        $tickerReceived = [];

        str_replace(' ', '', $ticker['symbol']);
        $ticker['symbol'] = preg_replace('/\s+/', '/', $ticker['symbol']);

        $explodedTicker = explode('/', $ticker['symbol']);
        $ticker['symbol'] = $explodedTicker[0];
        $tickerReceived['symbol'] = array_key_exists(1, $explodedTicker) ? $explodedTicker[1] : '--';

        trim($ticker['name']);
        $ticker['name'] = preg_replace('/\n+\s+/', '/', $ticker['name']);

        $explodedAssetName = explode('/', $ticker['name']);
        $ticker['name'] = $explodedAssetName[0];
        $tickerReceived['name'] = array_key_exists(1, $explodedAssetName) ? $explodedAssetName[1] : '--';

        $exchange['ticker'] = $ticker;
        $exchange['tickerReceived'] = $tickerReceived;

        return $exchange;
    }

    // if the transactions is stock option, we need to ensure trim the fat off of the ticker name since
    // we do not use that part of the data
    public function processStockOptionTransaction(Iterable $transaction)
    {
        $ticker = $transaction['ticker'];

        trim($ticker['name']);
        $ticker['name'] = explode("\n", $ticker['name'])[0];

        $transaction['ticker'] = $ticker;

        return $transaction;
    }

    public function filterOutNonStockTransactions(Collection $transactions)
    {
        return $transactions->whereIn('assetType', $this->allowableStockTypes);
    }

    // get transactions with no asset type
    public function pluckBlankAssetTypeTransactions($transaction)
    {
        return !(isset($transaction['assetType']))
            || $transaction['assetType'] === '--'
            || $transaction['assetType'] === '';
    }

    public function filterOutBlankTickers($transaction)
    {
        return isset($transaction['ticker']['symbol']) && $transaction['ticker']['symbol'] !== '--';
    }

    // if a ticker exists on a blank transaction type, we can assume it is stock
    public function fillBlankAssetTypesWithStock(Collection $transactions)
    {
        return $transactions->filter([$this, 'pluckBlankAssetTypeTransactions'])
            ->filter([$this, 'filterOutBlankTickers'])
            ->transform(function($transaction){
                $transaction['assetType'] = 'stock';
                return $transaction;
            });
    }
}