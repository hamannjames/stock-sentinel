<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Helpers\Connectors\ProPublicaConnector;

class ProPublicaConnectorTest extends TestCase
{
    /** @test */
    public function pro_publica_connector_can_retreive_senate_members()
    {
        $ppc = new ProPublicaConnector();
        $data = $ppc->index(['congress' => '113', 'chamber' => 'senate'])->current();

        $this->assertTrue(curl_getinfo($ppc->getSession(), \CURLINFO_HTTP_CODE) === 200);
    }
}
