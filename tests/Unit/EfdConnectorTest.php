<?php

namespace Tests\Unit;

use Generator;
use Tests\TestCase;
use App\Http\Helpers\EfdConnector;

class EfdConnectorTest extends TestCase
{
    /** @test */
    public function efd_connector_properly_connects_to_website()
    {
        $efd = new EfdConnector();
        $data = $efd->ptrIndex('01/01/2016', '12/31/2020');
        $this->assertFalse(is_null($data) || empty($data));
        $this->assertTrue(is_a($data, Generator::class));
        $this->assertTrue(isset($data->current()->recordsTotal));
        $this->assertTrue($data->current()->recordsTotal === 994);
        $this->assertTrue(count($data->current()->data) === $efd->getPtrRequestLength());
    }
}
