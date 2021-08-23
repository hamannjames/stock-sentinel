<?php

namespace App\Http\Helpers\Processors;

use Illuminate\Support\Collection;
use App\Http\Helpers\Processors\DataProcessor;

class EfdTransactionProcessor implements DataProcessor
{
    public $allowableStockTypes = ['stock', 'stock option'];

    public function processDataTable(Iterable $table) {
        if (!is_a($table, Collection::class)) {
            $table = collect($table);
        }

        $stockTransactions = $this->filterOutNonStockTransactions($table);
        $filledBlankTransactions = $this->fillBlankAssetTypesWithStock($table);

        return $stockTransactions->concat($filledBlankTransactions)->map([$this, 'processDataRow']);
    }

    public function processDataRow(Iterable $row) {
        if ($row['type'] === 'exchange') {
            return $this->processExchangeTransaction($row);
        }

        if ($row['assetType'] === 'stock option') {
            return $this->processStockOptionTransaction($row);
        }

        return $row;
    }

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