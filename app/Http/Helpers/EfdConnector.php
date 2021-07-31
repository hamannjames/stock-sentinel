<?php

namespace App\Http\Helpers;

class EfdConnector
{
    protected $base_url;
    protected $login_url;
    protected $search_url;
    protected $ptr_url;
    protected $data_url;
    protected $cookie_uri;
    public $session;
    protected $connected;
    protected $csrfMiddlewareToken;
    protected $status;
    protected $dateFormat;
    protected $ptrRequestLength;

    public function __construct()
    {
        $this->base_url = config('efd.base_url');
        $this->login_url = $this->base_url . config('efd.login_path') . '/';
        $this->search_url = $this->base_url . config('efd.search_path') . '/';
        $this->ptr_url = $this->base_url . config('efd.ptr_path') . '/';
        $this->data_url = $this->base_url . config('efd.data_path') . '/';
        $this->cookie_uri = config('efd.cookie_uri');
        $this->session = curl_init();
        $this->connected = false;
        $this->status = '';
        $this->dateFormat = 'm/d/Y';
        $this->ptrRequestLength = 100;
    }

    public function connect()
    {
        if ($this->connected) {
            $this->resetSession();
        }

        $result = $this->makeCookieRequest();
        $this->resetSession();
        $this->setSessionCookies($result);

        $result2 = $this->makeAgreementRequest();
        $this->setConnectedRequestOptions();

        $this->connected = true;
    }

    public function resetSession()
    {
        curl_close($this->session);
        $this->session = curl_init();
        $this->connected = false;

        return $this;
    }

    public function closeSession()
    {
        curl_close($this->session);
        $this->connected = false;

        return $this;
    }

    public function ptrIndex($start, $end)
    {
        $startDate = $this->validateDate($start);
        $endDate = $this->validateDate($end);

        if (!($startDate && $endDate)) {
            return 'Improperly formatted dates';
        }

        if ($endDate < $startDate) {
            return 'End Date must be greater than start date';
        }

        if (!$this->connected) {
            $this->connect();
        }

        if (!($this->status === 'ptr-index')) {
            $this->setPtrIndexRequestOptions();
        }
        
        $this->setPtrIndexRequestData($start, $end);

        $result = json_decode(curl_exec($this->session));

        yield $result;

        $offset = count($result->data);
        $offsetIncrease = $offset;

        while($result->recordsTotal > $offset) {
            if (!($this->status === 'ptr-index')) {
                $this->setPtrIndexRequestOptions();
            }

            $this->setPtrIndexRequestData($start, $end, $offset);

            $result2 = json_decode(curl_exec($this->session));

            $offset += $offsetIncrease;

            yield $result2;
        }

        return $result;
    }

    public function ptrShow($ptrId)
    {
        if (!$this->connected) {
            $this->connect();
        }

        if (!($this->status === 'ptr-show')) {
            $this->setPtrShowRequestOptions();
        }

        $this->setPtrShowRequestData($ptrId);

        return curl_exec($this->session);
    }

    protected function makeCookieRequest()
    {
        $this->setCookieRequestOptions();
        return curl_exec($this->session);
    }

    protected function setCookieRequestOptions()
    {
        curl_setopt_array($this->session, [
            CURLOPT_URL => $this->login_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookie_uri,
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
            CURLOPT_URL => $this->login_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookie_uri,
            CURLOPT_COOKIEFILE => $this->cookie_uri,
            CURLOPT_HEADER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => "csrfmiddlewaretoken={$this->csrfMiddlewareToken}&prohibition_agreement=1",
            CURLOPT_HTTPHEADER => [
                "Referer: {$this->login_url}",
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
    }

    private function setConnectedRequestOptions()
    {
        curl_setopt_array($this->session, [
            CURLOPT_HEADER => false
        ]);
    }

    private function setPtrIndexRequestOptions()
    {
        curl_setopt_array($this->session, [
            CURLOPT_URL => $this->data_url,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Referer: {$this->search_url}",
                "X-CSRFToken: {$this->csrfMiddlewareToken}",
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json'
            ]
        ]);

        $this->status = 'ptr-index';
    }

    protected function setPtrIndexRequestData($start, $end, $offset = 0)
    {
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

    private function setPtrShowRequestOptions()
    {
        curl_setopt_array($this->session, [
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => [
                "Referer: {$this->search_url}",
                "X-CSRFToken:",
                'Content-Type:',
                'Accept:'
            ]
        ]);

        $this->status = 'ptr-show';
    }

    protected function setPtrShowRequestData($id)
    {
        curl_setopt($this->session, CURLOPT_URL, $this->ptr_url . $id . '/');
    }

    private function validateDate($date)
    {
        $d = \DateTime::createFromFormat($this->dateFormat, $date);
        return $d && $d->format($this->dateFormat) === $date ? $d : false;
    }

    public function getPtrRequestLength()
    {
        return $this->ptrRequestLength;
    }
}