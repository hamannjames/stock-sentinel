<?php

namespace App\Http\Helpers\Connectors;

use Error;

// this class is a specific extension of a curl connector which knows how to connect to the efd
// database
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
        // get most of our needs from a config file
        $this->baseUrl = config('efd.base_url');
        $this->loginUrl = $this->baseUrl . config('efd.login_path') . '/';
        $this->searchUrl = $this->baseUrl . config('efd.search_path') . '/';
        $this->showUrl = $this->baseUrl . config('efd.ptr_path') . '/';
        $this->indexUrl = $this->baseUrl . config('efd.data_path') . '/';
        $this->cookieUri = config('efd.cookie_uri');
        $this->session = curl_init();
        $this->connected = false;
        $this->status = '';
        // arbitrary date format efd uses
        $this->dateFormat = 'm/d/Y';
        // arbitary request length of 100 ptrs
        $this->ptrRequestLength = 100;
    }

    public function startSession()
    {
        if ($this->connected) {
            return;
        }

        // for efd, to start we need to establish cookies
        $result = $this->makeCookieRequest();
        $this->resetSession();
        $this->setSessionCookies($result);

        // then we need to agree to terms
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
        // this request is mostly just an initial handshake to get some cookies
        curl_setopt_array($this->session, [
            CURLOPT_URL => $this->loginUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookieUri,
            CURLOPT_HEADER => true
        ]);
    }

    protected function setSessionCookies($cookieRequestResult)
    {
        // we need to pull the csrf token and set from the handshake
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
        // this is a post request to agree to the terms. we need the middleware token from the
        // handhsake
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
        // after the handhsake and agreement we do not need header info
        curl_setopt_array($this->session, [
            CURLOPT_HEADER => false
        ]);
    }

    protected function setIndexRequestOptions()
    {
        // index requests require a middleware token and are post requests
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
        // the offset determines which records we get. We start at 0
        $offset = !empty($params) && isset($params['offset']) ? $params['offset'] : 0;
        $start = $params['startDate'];
        $end = $params['endDate'];

        // some arbitrary data to get only ptr reports for senators
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
        // show requests are get requests and we do not need the token anymore
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
        // we need to set up the id in the url
        curl_setopt($this->session, CURLOPT_URL, $this->showUrl . $id . '/');
    }

    protected function isPaginated($result)
    {
        // we know the data is paginated if the records total are greater than the result
        return $result->recordsTotal > count($result->data);
    }

    protected function handlePagination($result)
    {
        $offset = count($result->data);
        $offsetIncrease = $offset;

        // while the total records are greater than our offset, pull more data
        while($result->recordsTotal > $offset) {
            if (!($this->status === 'index')) {
                $this->setIndexRequestOptions();
            }

            // pass in an offset so we keep pulling new data
            $this->setIndexRequestData([
                'startDate' => $this->currentIndexStart, 
                'endDate' => $this->currentIndexEnd, 
                'offset' => $offset
            ]);

            $result = json_decode(curl_exec($this->session));

            $offset += $offsetIncrease;

            // yield the result to the caller so we can keep going after it is processed
            yield $result;
        }
    }

    public function formatCurlResponse($result)
    {
        // we expect index results to be in json
        if ($this->status === 'index') {
            $result = json_decode($result);
        }

        return $result;
    }

    public function formatShowCurlResponse($result)
    {
        // show results are plain html
        return $result;
    }

    public function validateIndexParams($params = [])
    {
        // we need a start date and end date
        if (!isset($params['startDate']) || !isset($params['endDate'])) {
            throw new Error("Missing start or end date parameters");
        }

        $start = $params['startDate'];
        $end = $params['endDate'];

        // ensure dates are in right format
        $s = \DateTime::createFromFormat($this->dateFormat, $start);
        $e = \DateTime::createFromFormat($this->dateFormat, $end);

        if (!($s && $s->format($this->dateFormat) === $start)) {
            throw new Error("Index start does not adhere to date format {$this->dateFormat}");
        }

        if (!($e && $e->format($this->dateFormat) === $end)) {
            throw new Error("Index end does not adhere to date format {$this->dateFormat}");
        }

        // ensure start date is less than end date
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