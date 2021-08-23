<?php

namespace App\Http\Helpers\Connectors;

// this class is a specific extension of a curl connector for accessing pro publica senator data
class ProPublicaConnector extends CurlConnector
{
    protected $apiKey;
    protected $membersPath;

    public function __construct()
    {
        // get our config from config file and api key from env file
        $this->apiKey = env('PRO_PUBLICA_API_KEY');
        $this->baseUrl = config('proPublica.baseUrl');
        $this->indexUrl = $this->baseUrl . config('proPublica.congressPath') . config('proPublica.apiVersionPath');
        $this->membersPath = config('proPublica.membersPath');
        $this->showUrl = $this->indexUrl . $this->membersPath;
        $this->cookieUri = config('proPublica.cookieUri');
        $this->session = curl_init();
        $this->connected = false;
        $this->status = '';
    }

    public function startSession()
    {
        // no handshake required, we can just connect
        $this->connected = true;
    }

    public function validateIndexParams($params = [])
    {
        // we need congress and chamber params
        return isset($params['congress']) && isset($params['chamber']);
    }

    public function formatIndexParams($params = [])
    {
        // simply return params
        return $params;
    }

    public function formatCurlResponse($response)
    {
        // we want the json encoded results specifically from members
        return json_decode($response)->results[0]->members;
    }

    public function formatShowCurlResponse($response)
    {
        // we need the first row of data
        return json_decode($response)->results[0];
    }

    protected function setIndexRequestOptions()
    {
        // we only need to set the api key and return
        curl_setopt_array($this->session, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "X-API-KEY: {$this->apiKey}"
            ]
        ]);
    }

    protected function setIndexRequestData($params = [])
    {
        // only need the right url
        curl_setopt($this->session, CURLOPT_URL, $this->buildIndexUrl($params));
    }

    protected function setShowRequestOptions()
    {
        curl_setopt_array($this->session, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "X-API-KEY: {$this->apiKey}"
            ]
        ]);
    }

    protected function setShowRequestData($id, $options = [])
    {
        curl_setopt($this->session, CURLOPT_URL, $this->buildShowUrl($id));
    }

    public function buildIndexUrl($params)
    {
        // properly format the url for the apicall
        return "{$this->indexUrl}/{$params['congress']}/{$params['chamber']}{$this->membersPath}.json";
    }

    public function buildShowUrl($id)
    {
        return "{$this->showUrl}/{$id}.json";
    }

    protected function isPaginated($response)
    {
        // data is never paginated
        return false;
    }

    protected function handlePagination($response)
    {
        // we can simply return here, as this method should not be called
        return $response;
    }
}