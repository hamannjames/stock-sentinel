<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Ticker;
use App\Models\Transactor;
use App\Models\TransactionType;
use App\Http\Helpers\EfdConnector;
use App\Http\Helpers\PtrProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PtrProcessorTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function ptr_processor_does_not_find_transactions_with_stock_option_and_exchange()
    {
        $ptrProcessor = new PtrProcessor(new EfdConnector());

        // Guaranteed to return paginated collection
        $data = $ptrProcessor->connector->ptrIndex('01/01/2020', '12/31/2020');

        foreach($data as $ptrPage) {
            [$electronicPtrs, $paperPtrs] = $ptrProcessor->partitionElectronicPaperPtrs(collect($ptrPage->data));
            [$standardPtrs, $amendmentPtrs] = $ptrProcessor->partitionStandardAmendmentPtrs($electronicPtrs);

            foreach($standardPtrs as $key => $standardPtr) {
                $processed = $ptrProcessor->processStandardPtr($standardPtr, $key);
                
                $this->assertTrue($processed->get('transactions')
                    ->where('assetType', 'stock option')
                    ->where('type', 'exchange')
                    ->count() === 0);
            }
        }
    }

    /** @test */
    public function ptr_processor_correctly_parses_stock_option_transactions()
    {
        $ptrProcessor = new PtrProcessor(new EfdConnector());

        // Guaranteed to fetch ptrs with stock option transactions
        $data = $ptrProcessor->connector->ptrIndex('05/01/2020', '05/01/2020');
        $ptrWithStockOptions = $data->current()->data[1];

        $ptrId = $ptrProcessor->parsePtrId($ptrWithStockOptions[$ptrProcessor->getPtrLinkIndex()]);
        $ptrHtml = $ptrProcessor->fetchPtrPage($ptrId);
        $ptrTransactions = collect($ptrProcessor->parsePtrHtml($ptrHtml));

        $stockTransactions = $ptrTransactions->whereIn('assetType', ['stock', 'stock option']);
        $processedStockTransactions = $ptrProcessor->processStockTransactions($stockTransactions, $ptrWithStockOptions[0], $ptrWithStockOptions[1]);

        $this->assertTrue($processedStockTransactions->get('transactions')->last()['assetName'] === 'Aflac Incorporated');
    }

    /** @test */
    public function ptr_processor_correctly_partitions_ptr_data()
    {
        $ptrProcessor = new PtrProcessor(new EfdConnector());

        // Guaranteed to return paginated collection
        $data = $ptrProcessor->connector->ptrIndex('01/01/2020', '12/31/2020');
        $firstPage = collect($data->current()->data);

        $this->assertTrue($firstPage->count() === $ptrProcessor->connector->getPtrRequestLength());

        [$electronicPtrs, $paperPtrs] = $ptrProcessor->partitionElectronicPaperPtrs($firstPage);
        [$standardPtrs, $amendmentPtrs] = $ptrProcessor->partitionStandardAmendmentPtrs($electronicPtrs);

        $this->assertTrue($paperPtrs->count() + $standardPtrs->count() + $amendmentPtrs->count() === $ptrProcessor->connector->getPtrRequestLength());
    }

    /** @test */
    public function ptr_processor_correctly_parses_ptr_id()
    {
        $ptrProcessor = new PtrProcessor(new EfdConnector());

        // Guaranteed to return one PTR with exact PTR ID
        $data = $ptrProcessor->connector->ptrIndex('02/02/2021', '02/02/2021');
        $ptrId = 'cf66f482-baa0-409c-abdd-b9cfcbb5fd4e';
        $ptr = $data->current()->data;

        $this->assertTrue(count($ptr) === 1);
        $this->assertTrue(strpos($ptr[0][$ptrProcessor->getPtrLinkIndex()], $ptrId) > 0);
        $this->assertTrue($ptrProcessor->parsePtrId($ptr[0][$ptrProcessor->getPtrLinkIndex()]) === $ptrId);
    }

    /** @test */
    public function ptr_processor_correctly_retrieves_ptr_page()
    {   
        $ptrProcessor = new PtrProcessor(new EfdConnector());

        // Guaranteed to return one PTR with exact PTR ID
        $data = $ptrProcessor->connector->ptrIndex('02/02/2021', '02/02/2021');
        $ptr = $data->current()->data;

        $ptrId = $ptrProcessor->parsePtrId($ptr[0][$ptrProcessor->getPtrLinkIndex()]);
        $ptrHtml = $ptrProcessor->fetchPtrPage($ptrId);

        $this->assertTrue(curl_getinfo($ptrProcessor->connector->session, \CURLINFO_RESPONSE_CODE) === 200);
    }

    /** @test */
    public function ptr_processor_correctly_parses_ptr_html()
    {
        $ptrProcessor = new PtrProcessor(new EfdConnector());

        // Guaranteed to return PTR with multiple types of transactions
        $data = $ptrProcessor->connector->ptrIndex('07/28/2021', '07/28/2021');
        $ptr = $data->current()->data[0];
        $ptrId = $ptrProcessor->parsePtrId($ptr[$ptrProcessor->getPtrLinkIndex()]);

        $ptrTransactions = collect($ptrProcessor->parsePtrHtml($ptrProcessor->fetchPtrPage($ptrId)));

        $this->assertTrue($ptrTransactions->where('ticker', 'TMO')->count() > 0);
        $this->assertTrue($ptrTransactions->where('ticker', 'ADBE')->count() > 0);
        $this->assertTrue($ptrTransactions->where('assetType', 'stock')->count() === 2);
        $this->assertTrue($ptrTransactions->where('assetType', 'municipal security')->count() === 1);
    }

    /** @test */
    public function process_standard_ptr_correctly_returns_single_ptr_data()
    {
        $ptrProcessor = new PtrProcessor(new EfdConnector());

        // Guaranteed to return paginated collection
        $data = $ptrProcessor->connector->ptrIndex('01/01/2020', '12/31/2020');
        $firstPage = collect($data->current()->data);

        [$electronicPtrs, $paperPtrs] = $ptrProcessor->partitionElectronicPaperPtrs($firstPage);
        [$standardPtrs, $amendmentPtrs] = $ptrProcessor->partitionStandardAmendmentPtrs($electronicPtrs);

        // Guaranteed to be ptr with multiple stock transactions including exchanges
        $singlePtr = $standardPtrs->first();
        $processedPtr = $ptrProcessor->processStandardPtr($singlePtr, 1)->all();

        $this->assertTrue($processedPtr['tickers']->count() === 5);
        $this->assertTrue($processedPtr['types']->count() === 2);
        $this->assertTrue($processedPtr['transactor']['firstName'] === 'Patrick J');
        $this->assertTrue($processedPtr['transactor']['lastName'] === 'Toomey');
        $this->assertTrue($processedPtr['transactions']->count() === 4);
    }

    /** @test */
    public function ptr_processor_correctly_parses_and_processes_stock_exchanges()
    {
        $ptrProcessor = new PtrProcessor(new EfdConnector());
        $ptrProcessor->connector->connect();

        // Guaranteed ptr with exchange transaction
        $ptrHtml = $ptrProcessor->fetchPtrPage('d95fd568-e9c2-4715-b405-88c2758b44cc');
        $ptrTransactions = collect($ptrProcessor->parsePtrHtml($ptrHtml));
        $stockTransactions = $ptrTransactions->whereIn('assetType', ['stock', 'stock option']);
        $processedTransaction = $ptrProcessor->processExchangeTransactions($stockTransactions)->all()[1];

        $this->assertTrue($processedTransaction['ticker'] === 'FTV');
        $this->assertTrue($processedTransaction['tickerReceived'] === 'VNT');
        $this->assertTrue($processedTransaction['assetName'] === 'Fortive Corporation');
        $this->assertTrue($processedTransaction['assetNameReceived'] === 'Vontier Corporation');
    }

    /** @test */
    public function ptr_processor_proccess_standard_ptrs_returns_correct_account()
    {
        $ptrProcessor = new PtrProcessor(new EfdConnector());

        // Guaranteed to return paper, standard, stock, and non stock ptrs, sometimes mixed
        $data = $ptrProcessor->connector->ptrIndex('01/01/2020', '01/31/2020');
        $firstPage = collect($data->current()->data);

        [$electronicPtrs, $paperPtrs] = $ptrProcessor->partitionElectronicPaperPtrs($firstPage);
        [$standardPtrs, $amendmentPtrs] = $ptrProcessor->partitionStandardAmendmentPtrs($electronicPtrs);

        $this->assertTrue($ptrProcessor->processStandardPtrs($standardPtrs)->count() === 9);
    }

    /** @test */
    public function ptr_processor_correctly_finds_or_inserts_ticker_when_ticker_blank_on_transaction()
    {
        $ptrProcessor = new PtrProcessor(new EfdConnector());

        // Ptr guranteed to have blank tickers
        $ptrHtml = $ptrProcessor->fetchPtrPage('a78c3a39-385a-40e2-8062-c429eb485393');
        $ptrTransactions = collect($ptrProcessor->parsePtrHtml($ptrHtml));
        $stockTransactions = $ptrTransactions->whereIn('assetType', ['stock', 'stock option']);

        $exchangeWithEmpty = $ptrProcessor->parseExchangeTickers($stockTransactions->first(), 1);

        Ticker::factory()->create([
            'symbol' => 'test',
            'name' => 'BBT.F - BB&T CORP F PERPTL PFD'
        ]);

        $this->assertTrue(Ticker::count() === 1);

        $filledTransaction = $ptrProcessor->handleEmptyTicker($exchangeWithEmpty, 1);

        $this->assertTrue(Ticker::count() === 2);
        $this->assertTrue($filledTransaction['ticker'] === 'test');
        $this->assertTrue($filledTransaction['tickerReceived'] === '--1');
    }

    /** @test */
    public function ptr_processor_process_standard_ptrs_returns_no_blank_tickers()
    {
        $ptrProcessor = new PtrProcessor(new EfdConnector());

        // Guaranteed to return ptrs with blank tickers
        $data = $ptrProcessor->connector->ptrIndex('01/01/2020', '01/31/2020');
        $firstPage = collect($data->current()->data);

        [$electronicPtrs, $paperPtrs] = $ptrProcessor->partitionElectronicPaperPtrs($firstPage);
        [$standardPtrs, $amendmentPtrs] = $ptrProcessor->partitionStandardAmendmentPtrs($electronicPtrs);

        $processedPtrs = $ptrProcessor->processStandardPtrs($standardPtrs);
        $tickers = $ptrProcessor->pluckAndFlattenPtrTickers($processedPtrs);

        $this->assertFalse($tickers->isEmpty());
        $this->assertTrue($tickers->duplicates('symbol')->isEmpty());
        $this->assertTrue($tickers->where('symbol', '--')->isEmpty());
    }

    /** @test */
    public function ptr_processor_correctly_upserts_tickers()
    {
        $ptrProcessor = new PtrProcessor(new EfdConnector());

        // Guaranteed to return paper, standard, stock, and non stock ptrs, sometimes mixed
        $data = $ptrProcessor->connector->ptrIndex('01/01/2020', '01/31/2020');
        $firstPage = collect($data->current()->data);

        [$electronicPtrs, $paperPtrs] = $ptrProcessor->partitionElectronicPaperPtrs($firstPage);
        [$standardPtrs, $amendmentPtrs] = $ptrProcessor->partitionStandardAmendmentPtrs($electronicPtrs);

        $processedPtrs = $ptrProcessor->processStandardPtrs($standardPtrs);
        $tickers = $ptrProcessor->pluckAndFlattenPtrTickers($processedPtrs);

        $firstTicker = $tickers->whereNotIn('symbol', Ticker::all()->pluck('symbol')->toArray())->first();
        Ticker::factory()->create(['symbol' => $firstTicker['symbol'], 'name' => 'test']);

        $lastTicker = $tickers->whereNotIn('symbol', Ticker::all()->pluck('symbol')->toArray())->last();
        Ticker::factory()->create(['symbol' => $lastTicker['symbol'], 'name' => 'test']);

        $allTickers = $ptrProcessor->upsertPtrTickers($processedPtrs);

        $this->assertTrue($allTickers->count() === $tickers->count());
        $this->assertTrue($allTickers->where('symbol', $firstTicker['symbol'])->first()->name === $firstTicker['name']);
        $this->assertTrue($allTickers->where('symbol', $lastTicker['symbol'])->first()->name === $lastTicker['name']);
    }

    /** @test */
    public function ptr_processor_process_standard_ptrs_returns_types_correctly()
    {
        $ptrProcessor = new PtrProcessor(new EfdConnector());

        // Guaranteed to return ptrs with blank tickers
        $data = $ptrProcessor->connector->ptrIndex('01/01/2020', '01/31/2020');
        $firstPage = collect($data->current()->data);

        [$electronicPtrs, $paperPtrs] = $ptrProcessor->partitionElectronicPaperPtrs($firstPage);
        [$standardPtrs, $amendmentPtrs] = $ptrProcessor->partitionStandardAmendmentPtrs($electronicPtrs);

        $processedPtrs = $ptrProcessor->processStandardPtrs($standardPtrs);
        $types = $ptrProcessor->pluckAndFlattenPtrTransactionTypes($processedPtrs);

        $this->assertFalse($types->isEmpty());
        $this->assertTrue($types->duplicates('name')->isEmpty());
        $this->assertTrue($types->where('name', '--')->isEmpty());
    }

    /** @test */
    public function ptr_processor_correctly_upserts_types()
    {
        $ptrProcessor = new PtrProcessor(new EfdConnector());

        // Guaranteed to return paper, standard, stock, and non stock ptrs, sometimes mixed
        $data = $ptrProcessor->connector->ptrIndex('01/01/2020', '01/31/2020');
        $firstPage = collect($data->current()->data);

        [$electronicPtrs, $paperPtrs] = $ptrProcessor->partitionElectronicPaperPtrs($firstPage);
        [$standardPtrs, $amendmentPtrs] = $ptrProcessor->partitionStandardAmendmentPtrs($electronicPtrs);

        $processedPtrs = $ptrProcessor->processStandardPtrs($standardPtrs);
        $types = $ptrProcessor->pluckAndFlattenPtrTransactionTypes($processedPtrs);

        $firstType = $types->first();
        TransactionType::factory()->create(['name' => $firstType['name']]);

        $lastType = $types->last();
        TransactionType::factory()->create(['name' => $lastType['name']]);

        $allTypes = $ptrProcessor->upsertPtrTransactionTypes($processedPtrs);

        $this->assertTrue($allTypes->count() === $types->count());
    }

    /** @test */
    public function ptr_processor_process_standard_ptrs_returns_transactors_correctly()
    {
        $ptrProcessor = new PtrProcessor(new EfdConnector());

        // Guaranteed to return ptrs with blank tickers
        $data = $ptrProcessor->connector->ptrIndex('01/01/2020', '01/31/2020');
        $firstPage = collect($data->current()->data);

        [$electronicPtrs, $paperPtrs] = $ptrProcessor->partitionElectronicPaperPtrs($firstPage);
        [$standardPtrs, $amendmentPtrs] = $ptrProcessor->partitionStandardAmendmentPtrs($electronicPtrs);

        $processedPtrs = $ptrProcessor->processStandardPtrs($standardPtrs);
        $transactors = $ptrProcessor->pluckAndFlattenPtrTransactors($processedPtrs);

        $this->assertFalse($transactors->isEmpty());
        $this->assertTrue($transactors->duplicates('lastName')->isEmpty());
        $this->assertTrue($transactors->where('firstName', '--')->isEmpty());
    }

    /** @test */
    public function ptr_processor_correctly_upserts_transactors()
    {
        $ptrProcessor = new PtrProcessor(new EfdConnector());

        // Guaranteed to return paper, standard, stock, and non stock ptrs, sometimes mixed
        $data = $ptrProcessor->connector->ptrIndex('01/01/2020', '01/31/2020');
        $firstPage = collect($data->current()->data);

        [$electronicPtrs, $paperPtrs] = $ptrProcessor->partitionElectronicPaperPtrs($firstPage);
        [$standardPtrs, $amendmentPtrs] = $ptrProcessor->partitionStandardAmendmentPtrs($electronicPtrs);

        $processedPtrs = $ptrProcessor->processStandardPtrs($standardPtrs);
        $transactors = $ptrProcessor->pluckAndFlattenPtrTransactionTypes($processedPtrs);

        $firstTransactor = $transactors->first();
        Transactor::factory()->create(['name' => $firstType['name']]);

        $lastTransactor = $transactors->last();
        Transactor::factory()->create(['name' => $lastType['name']]);

        $allTransactors = $ptrProcessor->upsertPtrTransactionTypes($processedPtrs);

        $this->assertTrue($allTypes->count() === $types->count());
    }

    /** @test */
    public function ptr_processor_inserts_stock_into_transactions_with_no_asset_type_but_with_ticker()
    {
        $ptrProcessor = new PtrProcessor(new EfdConnector());
        $ptrProcessor->connector->connect();

        // Guaranteed ptr with blank asset types
        $ptrHtml = $ptrProcessor->fetchPtrPage('52ba7b0b-343b-440d-8465-06987c9fbebb');
        $ptrTransactions = collect($ptrProcessor->parsePtrHtml($ptrHtml));
        $stockTransactions = $ptrTransactions->whereIn('assetType', ['stock', 'stock option']);
        $blankTransactions = $ptrTransactions->where('assetType', '');

        $this->assertTrue($stockTransactions->isEmpty());
        $this->assertTrue($blankTransactions->count() === 7);

        $filledTransactions = $blankTransactions->filter([$ptrProcessor, 'filterOutBlankTicker'])->map(function($transaction, $key){
            $transaction['assetType'] = 'stock';
            return $transaction;
        });

        $this->assertTrue($filledTransactions->count() === $blankTransactions->count());
        $this->assertTrue($filledTransactions->where('assetType', '')->isEmpty());
    }
}
