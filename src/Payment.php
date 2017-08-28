<?php

namespace Glayzie\Faspay;

use Illuminate\Support\Collection;

class Payment
{
    protected $data;

    protected $tax;

    protected $nominals = [
        'bill_tax',
        'bill_total',
        'bill_gross',
        'bill_miscfee'
    ];

    protected static $channels = [
        'tcash' => 301, 
        'bri_mocash' => 400, 
        'bri_epay' => 401, 
        'permata' => 402, 
        'mBCA' => 403, 
        'klikbca' => 404, 
        'klikpaybca' => 405, 
        'clickpayMandiri' => 406, 
        'bii_sms' => 407,
        'bii_pay' => 408,
    ];

    protected static $terminals = [
        'web' => 10,
        'blackberry_mobapp' => 20,
        'android_mobapp' => 21,
        'ios_mobapp' => 22,
        'windows_mobapp' => 23,
        'symbian_mobapp' => 24,
        'blackberry_tabapp' => 30,
        'android_tabapp' => 31,
        'ios_tabapp' => 32,
        'windows_tabapp' => 33,
    ];

    protected $defaults = [
        'request' => 'Post Data Transaksi',
        'terminal' => 10,
        'pay_type' => 1,
        'bill_tax' => 0,
        'bill_miscfee' => 0
    ];

    protected $fields = [
        'request',
        'merchant_id',
        'merchant',
        'bill_no',
        'bill_reff',
        'bill_date',
        'bill_expired',
        'bill_desc',
        'bill_currency',
        'bill_gross',
        'bill_tax',
        'bill_miscfee',
        'bill_total',
        'cust_no',
        'cust_name',
        'payment_channel',
        'pay_type',
        'bank_userid',
        'msisdn',
        'email',
        'terminal',
        'billing_address',
        'billing_address_city',
        'billing_address_region',
        'billing_address_state',
        'billing_address_poscode',
        'billing_address_country_code',
        'receiver_name_for_shipping',
        'shipping_address',
        'shipping_address_city',
        'shipping_address_region',
        'shipping_address_state',
        'shipping_address_poscode',
        'signature',
    ];

    public function __construct($data = [])
    {
        $this->data = new Collection(
            $data + $this->defaults + 
            array_fill_keys($this->fields, null) + [
                'items' => new Collection()
            ]
        );
    }

    public function performedBy(CustomerInterface $customer)
    {
        $this->merge([
            'cust_no' => $customer->getFaspayCustomerNumber(),
            'cust_name' => $customer->getFaspayCustomerName(),
            'email' => $customer->getFaspayCustomerEmail(),
            'msisdn' => $customer->getFaspayCustomerPhone(),
            'bill_currency' => $customer->getFaspayPreferredCurrency(),
        ]);

        return $this;
    }

    public function payWith($channelName)
    {
        $this->data->put('payment_channel', static::$channels[$channelName]);

        return $this;
    }

    public function getTransactionId()
    {
        return $this->data->get('trx_id');
    }

    public function hasRegistered()
    {
        return $this->data->has('trx_id') and ! $this->hasError();
    }

    public function hasError()
    {
        return $this->data->get('response_code') !== '00';
    }

    public function getError()
    {
        return $this->data->get('response_desc');
    }

    public function getCode()
    {
        return $this->data->get('response_code');
    }

    public function addItem(Payable $item, $quantity = 1)
    {
        $this->data['items']->push([
            'product' => $item->getPayableName(),
            'amount' => $item->getPayablePrice() . '00',
            'qty' => $quantity,
            'payment_plan' => 1,
            'tenor' => 1,
            'merchant_id' => ''
        ]);

        $this->updateNumbers();

        return $this;
    }

    public function via($terminalName)
    {
        $this->data->put('terminal', static::$terminals[$terminalName]);

        return $this;
    }

    public function addTax($percent)
    {
        $this->tax = $percent;

        $this->updateNumbers();

        return $this;
    }

    public function addMiscFee($amount)
    {
        $this->bill_miscfee = $amount;

        $this->updateNumbers();

        return $this;
    }

    public function getCalculatedTax()
    {
        return ($this->tax > 0) ? $this->tax / 100 * $this->getCalculatedGross() : 0;
    }

    public function getCalculatedGross()
    {
        return $this->data->get('items')->sum(function($item) {
            return $item['qty'] * substr($item['amount'], 0, -2);
        });
    }

    public function getCalculatedTotal()
    {
        return $this->getCalculatedGross() + 
               $this->getCalculatedTax() + 
               $this->bill_miscfee;
    }

    protected function updateNumbers()
    {
        $this->bill_tax = $this->getCalculatedTax();
        $this->bill_total = $this->getCalculatedTotal();
        $this->bill_gross = $this->getCalculatedGross();
    }

    public function merge($data)
    {
        $this->data = $this->data->merge($data);

        return $this;
    }

    public function data()
    {
        return $this->data->all();
    }

    public function __get($name)
    {
        if (! in_array($name, $this->fields)) {
            throw new \Exception('Property not found: ' . $name);
        }

        if (in_array($name, $this->nominals)) {
            return substr($this->data->get($name), 0, -2);
        }

        return $this->data->get($name);
    }

    public function __set($name, $value)
    {
        if (! in_array($name, $this->fields)) {
            throw new \Exception('Property not found: ' . $name);
        }

        $this->data->put(
            $name, in_array($name, $this->nominals) ? $value . '00' : $value
        );
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->data, $name], $arguments);
    }
}