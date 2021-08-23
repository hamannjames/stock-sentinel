<?php

namespace Tests\Unit;

use Generator;
use Tests\TestCase;
use App\Http\Helpers\Connectors\EfdConnector;

class EfdConnectorTest extends TestCase
{
    /** @test */
    public function efd_connector_properly_connects_to_website()
    {
        $efd = new EfdConnector();
        $data = $efd->index([
            'startDate' => '01/01/2016', 
            'endDate' => '12/31/2020'
        ]);
        $this->assertFalse(is_null($data) || empty($data));
        $this->assertTrue(is_a($data, Generator::class));
        $this->assertTrue(isset($data->current()->recordsTotal));
        $this->assertTrue($data->current()->recordsTotal === 994);
        $this->assertTrue(count($data->current()->data) === $efd->getPtrRequestLength());
    }

    /** @test */
    public function efd_connector_correctly_paginated_data_using_abstract_class()
    {
        $efd = new EfdConnector();
        $efd->setPtrRequestLength(100);

        // guaranteed to retrieve 239 rows
        $data = $efd->index([
            'startDate' => '01/01/2020', 
            'endDate' => '06/30/2021'
        ]);

        $firstPage = $data->current();
        $this->assertTrue($firstPage->recordsTotal === 239);
        $this->assertTrue(count($firstPage->data) === 100);

        $data->next();
        $secondPage = $data->current();
        $this->assertTrue(count($secondPage->data) === 100);

        $data->next();
        $thirdPage = $data->current();
        $this->assertTrue(count($thirdPage->data) === 39);

        $data->next();
        $this->assertNull($data->current());
    }

    /** @test */
    public function ef_connector_can_retrieve_single_page()
    {
        $efd = new EfdConnector();

        // Guaranteed to pull single ptr with transactions;
        $page = $efd->show('79a6c9ae-2a6a-4194-86d8-ab66885bdb3b');
        
        
    }
}
