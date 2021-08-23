<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\App;
use App\Http\Helpers\Processors\PtrProcessor;

class EfdTransactionProcessorTest extends TestCase
{
    public function setup(): void
    {
        parent::setup();
        $this->ptrp = App::make(PtrProcessor::class);
        $this->etp = $this->ptrp->getEfdTransactionProcessor();
    }

    /** @test */
    public function processor_correctly_segments_stock_transactions()
    {
        // Guaranteed ptr with stock and non-stock transactions
        $transactions = collect($this->ptrp->parsePtrHtml($this->ptrp->fetchPtrPage('2c09f31e-0a1f-4905-9a71-7750a856111d')));
        $stocks = $this->etp->filterOutNonStockTransactions($transactions);

        $this->assertTrue($transactions->isNotEmpty());
        $this->assertTrue($stocks->isNotEmpty());
        $this->assertTrue($stocks->count() < count($transactions));
        $this->assertTrue($transactions->whereNotIn('assetType', $this->etp->allowableStockTypes)->isNotEmpty());
        $this->assertTrue($stocks->whereNotIn('assetType', $this->etp->allowableStockTypes)->isEmpty());
    }

    /** @test */
    public function processor_correctly_processes_stock_option_transactions()
    {
        // Ptr with guaranteed stock options
        $ptrHtml = $this->ptrp->fetchPtrPage('8d87d0d9-8094-4891-a29c-c0e0435acb1a');
        $ptrTransactions = collect($this->ptrp->parsePtrHtml($ptrHtml));
        $stockOptionTransactions = $ptrTransactions->where('assetType', 'stock option');
        
        $this->etp->processDataTable($stockOptionTransactions)->each(function($t) {
            $this->assertFalse(strpos($t['ticker']['name'], "\n"));
            $this->assertTrue(strlen($t['ticker']['name']) > 0);
        });
    }

    /** @test */
    public function processor_correctly_processes_exchange_transactions()
    {
        // Ptr with guaranteed exchange transactions
        $ptrHtml = $this->ptrp->fetchPtrPage('d95fd568-e9c2-4715-b405-88c2758b44cc');
        $ptrTransactions = collect($this->ptrp->parsePtrHtml($ptrHtml));
        $exchangeTransactions = $ptrTransactions->filter(function($transaction) {
            return ($transaction['assetType'] === 'stock' || $transaction['assetType'] === 'stock option') && $transaction['type'] === 'exchange';
        });
        
        $this->etp->processDataTable($exchangeTransactions)->each(function($t) {
            $ticker = $t['ticker'];
            $this->assertFalse(strpos($ticker['symbol'], "\n"));
            $this->assertFalse(strpos($ticker['name'], "\n"));

            $this->assertTrue(isset($t['tickerReceived']));

            if (isset($t['tickerReceived'])) {
                $tickerReceived = $t['tickerReceived'];
                $this->assertFalse(strpos($tickerReceived['symbol'], "\n"));
                $this->assertFalse(strpos($tickerReceived['name'], "\n"));
                $this->assertTrue($tickerReceived['symbol'] !== $ticker['symbol']);
                $this->assertTrue($tickerReceived['name'] !== $ticker['name']);
            }
        });
    }

    /** @test */
    public function processor_correctly_plucks_blank_transactions()
    {
        // Ptr with guaranteed blank asset types
        $ptrHtml = $this->ptrp->fetchPtrPage('52ba7b0b-343b-440d-8465-06987c9fbebb');
        $ptrTransactions = collect($this->ptrp->parsePtrHtml($ptrHtml));

        $blankTransactions = $ptrTransactions->filter([$this->etp, 'pluckBlankAssetTypeTransactions']);
        $filledTransactions = $ptrTransactions->filter(function($transaction){
            return !$this->etp->pluckBlankAssetTypeTransactions($transaction);
        });

        $this->assertTrue($blankTransactions->count() !== $filledTransactions->count());
        $this->assertTrue($blankTransactions->isNotEmpty());
        $this->assertTrue($blankTransactions->where('assetType', '!=', '')->isEmpty());
    }

    /** @test */
    public function processor_correctly_filters_blank_tickers()
    {
        // Ptr with guaranteed blank asset types
        $ptrHtml = $this->ptrp->fetchPtrPage('52ba7b0b-343b-440d-8465-06987c9fbebb');
        $ptrTransactions = collect($this->ptrp->parsePtrHtml($ptrHtml));

        $filledTickers = $ptrTransactions->filter([$this->etp, 'filterOutBlankTickers']);
        $blankTickers = $ptrTransactions->filter(function($transaction){
            return !$this->etp->filterOutBlankTickers($transaction);
        });

        $this->assertTrue($filledTickers->count() !== $blankTickers->count());
        $this->assertTrue($filledTickers->isNotEmpty());
        $this->assertTrue($filledTickers->count() === $ptrTransactions->count());
    }

    /** @test */
    public function processor_correctly_fills_blank_transactions_with_tickers_with_stock()
    {
        // Ptr with guaranteed blank asset types
        $ptrHtml = $this->ptrp->fetchPtrPage('52ba7b0b-343b-440d-8465-06987c9fbebb');
        $ptrTransactions = collect($this->ptrp->parsePtrHtml($ptrHtml));

        $filledTransactions = $this->etp->fillBlankAssetTypesWithStock($ptrTransactions);

        $this->assertTrue($filledTransactions->count() === $ptrTransactions->count());
        $this->assertTrue($filledTransactions->where('assetType', '!=', 'stock')->isEmpty());
    }
}
