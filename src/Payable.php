<?php

namespace Glayzie\Faspay;

interface Payable
{
    public function getPayableName();

    public function getPayablePrice();
}