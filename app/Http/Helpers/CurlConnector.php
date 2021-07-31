<?php

namespace App\Http\Helpers;

use Generator;

abstract class CurlConnector implements ApiConnector
{
    protected \CurlHandle $session;
    protected $baseUrl;
    protected $indexUrl;
    protected $showUrl;
    protected $cookieUri;
    protected $connected;
    protected $status;
    protected $returnFormat;

    abstract public function startSession();
    abstract public function validateIndexParams($start, $function);
    abstract public function formatIndexStart();
    abstract public function formatIndexEnd();
    abstract protected function setIndexRequestOptions();
    abstract protected function setShowRequestOptions();
    abstract protected function setIndexRequestData($start, $end, $options = []);
    abstract protected function setShowRequestData($id, $options = []);
    abstract protected function formatCurlResponse($response);
    abstract protected function handlePagination($response);

    public function closeSession()
    {
        curl_close($this->session);
        $this->connected = false;
    }

    public function resetSession() {
        $this->closeSession();
        $this->startSession();
    }

    public function index($start, $end)
    {
        if (!$this->validateIndexParams($start, $end)) {
            throw new Error('Invalid index parameters');
        }

        if (!$this->connected) {
            $this->startSession();
        }

        if (!$this->status === 'index') {
            $this->setIndexRequestOptions();
        }

        $this->setIndexRequestData($this->formatIndexStart($start), $this->formatIndexEnd($end));

        $result = $this->formatCurlResponse(curl_exec($this->session));

        yield $result;

        $this->handlePagination($result);
    }

    public function show($id)
    {
        if (!$this->connected) {
            $this->connect();
        }

        if (!($this->status === 'show')) {
            $this->setShowRequestOptions();
        }

        $this->setShowRequestData($id);

        return $this->formatCurlResonse(curl_exec($this->session));
    }
}