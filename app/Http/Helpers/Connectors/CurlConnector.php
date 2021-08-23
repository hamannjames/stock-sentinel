<?php

namespace App\Http\Helpers\Connectors;

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
    abstract public function validateIndexParams($params = []);
    abstract public function formatIndexParams($params = []);
    abstract public function formatCurlResponse($response);
    abstract public function formatShowCurlResponse($response);
    abstract protected function setIndexRequestOptions();
    abstract protected function setShowRequestOptions();
    abstract protected function setIndexRequestData($options = []);
    abstract protected function setShowRequestData($id, $options = []);
    abstract protected function handlePagination($response);
    abstract protected function isPaginated($response);

    public function closeSession()
    {
        curl_close($this->session);
        $this->connected = false;
    }

    public function resetSession() {
        $this->closeSession();
        $this->session = curl_init();
    }

    public function index($params)
    {
        if (!$this->validateIndexParams($params)) {
            throw new Error('Invalid index parameters');
        }

        if (!$this->connected) {
            $this->startSession();
        }

        if (!($this->status === 'index')) {
            $this->setIndexRequestOptions();
            $this->status = 'index';
        }

        $this->setIndexRequestData($this->formatIndexParams($params));

        $result = $this->formatCurlResponse(curl_exec($this->session));

        yield $result;

        if ($this->isPaginated($result)) {
            foreach($this->handlePagination($result) as $page) {
                yield $page;
            }
        }
    }

    public function show($id)
    {
        if (!$this->connected) {
            $this->startSession();
        }

        if (!($this->status === 'show')) {
            $this->setShowRequestOptions();
            $this->status = 'show';
        }

        $this->setShowRequestData($id);

        return $this->formatShowCurlResponse(curl_exec($this->session));
    }

    public function getSession()
    {
        return $this->session;
    }
}