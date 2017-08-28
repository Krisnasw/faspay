<?php

namespace Glayzie\Faspay;

interface CustomerInterface
{
    public function getFaspayCustomerNumber();
    public function getFaspayCustomerName();
    public function getFaspayCustomerEmail();
    public function getFaspayCustomerPhone();
}