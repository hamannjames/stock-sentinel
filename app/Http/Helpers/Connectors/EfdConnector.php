<?php

namespace App\Http\Helpers\Connectors;

use Error;

class EfdConnector extends CurlConnector
{
    protected $loginUrl;
    protected $searchUrl;
    protected $csrfMiddlewareToken;
    protected $dateFormat;
    protected $ptrRequestLength;
    protected $currentIndexStart;
    protected $currentIndexEnd;

    public function __construct()
    {
        $this->baseUrl = config('efd.base_url');
        $this->loginUrl = $this->baseUrl . config('efd.login_path') . '/';
        $this->searchUrl = $this->baseUrl . config('efd.search_path') . '/';
        $this->showUrl = $this->baseUrl . config('efd.ptr_path') . '/';
        $this->indexUrl = $this->baseUrl . config('efd.data_path') . '/';
        $this->cookieUri = config('efd.cookie_uri');
        $this->session = curl_init();
        $this->connected = false;
        $this->status = '';
        $this->dateFormat = 'm/d/Y';
        $this->ptrRequestLength = 100;
    }

    public function startSession()
    {
        if ($this->connected) {
            return;
        }

        $result = $this->makeCookieRequest();
        $this->resetSession();
        $this->setSessionCookies($result);

        $result2 = $this->makeAgreementRequest();
        $this->setConnectedRequestOptions();

        $this->connected = true;
    }

    protected function makeCookieRequest()
    {
        $this->setCookieRequestOptions();
        return curl_exec($this->session);
    }

    protected function setCookieRequestOptions()
    {
        curl_setopt_array($this->session, [
            CURLOPT_URL => $this->loginUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookieUri,
            CURLOPT_HEADER => true
        ]);
    }

    protected function setSessionCookies($cookieRequestResult)
    {
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $cookieRequestResult,  $match_found);

        $cookies = [];
        
        foreach($match_found[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }

        $this->csrfMiddlewareToken = $cookies['csrftoken'];
    }

    protected function makeAgreementRequest()
    {
        $this->setAgreementRequestOptions();
        return curl_exec($this->session);
    }

    protected function setAgreementRequestOptions()
    {
        curl_setopt_array($this->session, [
            CURLOPT_URL => $this->loginUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookieUri,
            CURLOPT_COOKIEFILE => $this->cookieUri,
            CURLOPT_HEADER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => "csrfmiddlewaretoken={$this->csrfMiddlewareToken}&prohibition_agreement=1",
            CURLOPT_HTTPHEADER => [
                "Referer: {$this->loginUrl}",
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
    }

    protected function setConnectedRequestOptions()
    {
        curl_setopt_array($this->session, [
            CURLOPT_HEADER => false
        ]);
    }

    protected function setIndexRequestOptions()
    {
        curl_setopt_array($this->session, [
            CURLOPT_URL => $this->indexUrl,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Referer: {$this->searchUrl}",
                "X-CSRFToken: {$this->csrfMiddlewareToken}",
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json'
            ]
        ]);
    }

    protected function setIndexRequestData($params = [])
    {
        $offset = !empty($params) && isset($params['offset']) ? $params['offset'] : 0;
        $start = $params['startDate'];
        $end = $params['endDate'];

        $postData = [
            'start' => $offset,
            'length' => $this->ptrRequestLength,
            'report_types' => '[11]',
            'filer_types' => '[1]',
            'submitted_start_date' => "{$start} 00:00:00",
            'submitted_end_date' => "{$end} 23:59:59"
        ];

        $postString = http_build_query($postData);

        curl_setopt($this->session, CURLOPT_POSTFIELDS, $postString);
    }

    protected function setShowRequestOptions()
    {
        curl_setopt_array($this->session, [
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => [
                "Referer: {$this->searchUrl}",
                "X-CSRFToken:",
                'Content-Type:',
                'Accept:'
            ]
        ]);
    }

    protected function setShowRequestData($id, $options = [])
    {
        curl_setopt($this->session, CURLOPT_URL, $this->showUrl . $id . '/');
    }

    protected function isPaginated($result)
    {
        return $result->recordsTotal > count($result->data);
    }

    protected function handlePagination($result)
    {
        $offset = count($result->data);
        $offsetIncrease = $offset;

        while($result->recordsTotal > $offset) {
            if (!($this->status === 'index')) {
                $this->setIndexRequestOptions();
            }

            $this->setIndexRequestData([
                'startDate' => $this->currentIndexStart, 
                'endDate' => $this->currentIndexEnd, 
                'offset' => $offset
            ]);

            $result = json_decode(curl_exec($this->session));

            $offset += $offsetIncrease;

            yield $result;
        }
    }

    public function formatCurlResponse($result)
    {
        if ($this->status === 'index') {
            $result = json_decode($result);
        }

        return $result;
    }

    public function validateIndexParams($params = [])
    {
        if (!isset($params['startDate']) || !isset($params['endDate'])) {
            throw new Error("Missing start or end date parameters");
        }

        $start = $params['startDate'];
        $end = $params['endDate'];

        $s = \DateTime::createFromFormat($this->dateFormat, $start);
        $e = \DateTime::createFromFormat($this->dateFormat, $end);

        if (!($s && $s->format($this->dateFormat) === $start)) {
            throw new Error("Index start does not adhere to date format {$this->dateFormat}");
        }

        if (!($e && $e->format($this->dateFormat) === $end)) {
            throw new Error("Index end does not adhere to date format {$this->dateFormat}");
        }

        if ($e < $s) {
            throw new Error("Index end cannot come before index start");
        }

        return true;
    }

    public function formatIndexParams($params = []) {
        $this->currentIndexStart = $params['startDate'];
        $this->currentIndexEnd = $params['endDate'];

        return $params;
    }

    public function getPtrRequestLength()
    {
        return $this->ptrRequestLength;
    }

    public function getDateFormat()
    {
        return $this->dateFormat;
    }

    public function setPtrRequestLength(int $length)
    {
        $this->ptrRequestLength = $length;
    }
}