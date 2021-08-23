<?php

namespace App\Http\Helpers\Connectors;

use Generator;

// the curl connector is a specific implementation of the api connector interface that uses curl
abstract class CurlConnector implements ApiConnector
{
    // most api requests with curl will need a base url, index url, show url (for single resources)
    // as well as a place to store cookies and a need to format the data
    protected \CurlHandle $session;
    protected $baseUrl;
    protected $indexUrl;
    protected $showUrl;
    protected $cookieUri;
    protected $connected;
    protected $status;
    protected $returnFormat;

    // Leave these methods up to individual extensions, but they should exist
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

    // close the curl session and set connected to false
    public function closeSession()
    {
        curl_close($this->session);
        $this->connected = false;
    }

    // close session then set session to new instance of curlhandler
    public function resetSession() {
        $this->closeSession();
        $this->session = curl_init();
    }

    public function index($params)
    {
        if (!$this->validateIndexParams($params)) {
            throw new Error('Invalid index parameters');
        }

        // always validate index params
        if (!$this->connected) {
            $this->startSession();
        }

        // curl connectors should know how to prepare curl session for an index request
        if (!($this->status === 'index')) {
            $this->setIndexRequestOptions();
            $this->status = 'index';
        }

        // set index request data from params
        $this->setIndexRequestData($this->formatIndexParams($params));

        // format the result
        $result = $this->formatCurlResponse(curl_exec($this->session));

        // we yield the result, creating a generator.
        yield $result;

        // If the result is paginated, which every instance should know how to decipher, we can 
        // handle the pagination then yield each result.
        if ($this->isPaginated($result)) {
            foreach($this->handlePagination($result) as $page) {
                yield $page;
            }
        }
    }

    // gather an individual resource
    public function show($id)
    {
        // every curl instance should know how to start session
        if (!$this->connected) {
            $this->startSession();
        }

        // set show request options
        if (!($this->status === 'show')) {
            $this->setShowRequestOptions();
            $this->status = 'show';
        }

        $this->setShowRequestData($id);

        return $this->formatShowCurlResponse(curl_exec($this->session));
    }

    // return the curl handler
    public function getSession()
    {
        return $this->session;
    }
}