<?php

namespace Krisnasw\Faspay;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Spatie\ArrayToXml\ArrayToXml;
use GuzzleHttp\Exception\ServerException;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Faspay {

    protected $userid;
    protected $password;
    protected $merchantCode;
    protected $merchantName;
    protected $production;
    protected $expirationHours = 1;
    
    public function __construct($userid, $password, $merchantCode, $merchantName, array $config = [])
    {
        $this->userid = $userid;
        $this->password = $password;
        $this->merchantCode = $merchantCode;
        $this->merchantName = $merchantName;
        $this->production = $config['production'];
        $this->expirationHours = isset($config['expiration_hours']) ? $config['expiration_hours'] : $this->expirationHours;
    }

    public function getBillingUrl()
    {
        return $this->production ? 
          'https://faspay.mediaindonusa.com/pws/300002/383xx00010100000' :
          'http://faspaydev.mediaindonusa.com/pws/300002/183xx00010100000';
    }

    public function getRedirectUrl()
    {
        return $this->production ? 
          'https://faspay.mediaindonusa.com/pws/100003/2830000010100000' :
          'http://faspaydev.mediaindonusa.com/pws/100003/0830000010100000';
    }

    public function registerPayment(Payment $payment, BillingProfileInterface $billingProfile = null)
    {
        $billingProfile = $billingProfile ?: new GenericBillingProfile();
        $billNo = $billingProfile->generate($payment);
        $billReff = ($payment->payment_channel == 'tcash') ? 555555 : $billNo;
        $payment->merge([
            'merchant_id' => $this->merchantCode, 
            'merchant' => $this->merchantName, 
            'signature' => $this->makeSignature($billNo),
            'bill_no' => $billNo,
            'bill_desc' => $billingProfile->description(),
            'bill_reff' => $billReff,
            'bill_date' => Carbon::now(),
            'bill_expired' => Carbon::now()->addHours($this->expirationHours),
        ]);
        $requestXml = $this->formatAsXml($payment->data());
        $response = $this->sendRequest(
            $this->getBillingUrl(), $requestXml
        );
        $responseXml = simplexml_load_string($response);
        $payment->merge([
            'trx_id' => (string) $responseXml->trx_id,
            'response_code' => (string) $responseXml->response_code,
            'response_desc' => (string) $responseXml->response_desc,
        ]);
        return $response;
    }

    public function redirectToPay(Payment $payment)
    {
        $params = $payment->only(['trx_id', 'merchant_id', 'bill_no'])->all();
        return new RedirectResponse(
            $this->getRedirectUrl() . '/' . $payment->signature . '?' . http_build_query($params)
        );
    }

    public function notified($raw, $callback)
    {
        $notification = new Notification($raw);
        $callback($notification);
        return $notification;
    }

    public function sendRequest($url, $rawXml)
    {
        $response = $this->getHttpClient()->post($url, [
            'body' => $rawXml,
            'headers' => [
                'Content-Type' => 'text/xml'
            ]
        ]);
        return $response->getBody()->getContents();
    }

    protected function getHttpClient()
    {
        if (! $this->httpClient) {
            $this->httpClient = new Client([
                'cookies' => true,
                'headers' => [
                    'User-Agent' => $this->useragent
                ]
            ]);
        }
        return $this->httpClient;
    }
    
    public function setHttpClient(Client $client)
    {
        $this->httpClient = $client;
        return $this;
    }
  
    private function makeSignature($billNo)
    {
        return sha1(md5($this->userid . $this->password . $billNo));
    }
    
    private function formatAsXml(array $data)
    {
        $items = '';
        if (array_key_exists('items', $data)) {
            foreach ($data['items'] as $item) {
                $items.= str_replace('<?xml version="1.0"?>', '', 
                    ArrayToXml::convert($item, 'item')
                );
            }
            unset($data['items']);
        }
        return str_replace('</faspay>', $items . '</faspay>', 
            ArrayToXml::convert($data, 'faspay')
        );
    }
}
