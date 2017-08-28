<?php

namespace Krisnasw\Faspay;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Request;

class Notification
{
    protected $raw;

    protected $data;

    protected $fields = [
        'request',
        'trx_id',
        'merchant_id',
        'bill_no',
        'payment_status_code',
        'payment_status_desc',
        'payment_date',
        'amount',
    ];

    protected $dates = [
        'payment_date'
    ];

    public function __construct(Request $request)
    {
        $xml = simplexml_load_string($request->getContent());

        $this->raw = $rawXml;
        $this->data = new Collection();

        foreach ($this->fields as $field) {
            $this->data->put($field, $this->formatField($xml, $field));
        }
    }

    public function getRaw()
    {
        return $this->raw;
    }

    public function __get($name)
    {
        if (! in_array($name, $this->fields)) {
            throw new \Exception('Property not found: ' . $name);
        }

        return $this->data->get($name);
    }

    protected function formatField($xml, $field)
    {
        return in_array($field, $this->dates) 
            ? Carbon::createFormFormat('Y-m-d H:i:s', (string) $xml->{$field})
            : (string) $xml->{$field};
    }
}