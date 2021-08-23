<?php

namespace App\Http\Helpers\Connectors;

class ProPublicaConnector extends CurlConnector
{
    protected $apiKey;
    protected $membersPath;

    public function __construct()
    {
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
        $this->connected = true;
    }

    public function validateIndexParams($params = [])
    {
        return isset($params['congress']) && isset($params['chamber']);
    }

    public function formatIndexParams($params = [])
    {
        return $params;
    }

    public function formatCurlResponse($response)
    {
        return json_decode($response)->results[0]->members;
    }

    public function formatShowCurlResponse($response)
    {
        return json_decode($response)->results[0];
    }

    protected function setIndexRequestOptions()
    {
        curl_setopt_array($this->session, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "X-API-KEY: {$this->apiKey}"
            ]
        ]);
    }

    protected function setIndexRequestData($params = [])
    {
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
        return "{$this->indexUrl}/{$params['congress']}/{$params['chamber']}{$this->membersPath}.json";
    }

    public function buildShowUrl($id)
    {
        return "{$this->showUrl}/{$id}.json";
    }

    protected function isPaginated($response)
    {
        return false;
    }

    protected function handlePagination($response)
    {
        return $response;
    }
}