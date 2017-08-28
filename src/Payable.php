<?php

namespace Krisnasw\Faspay;

interface Payable
{
    public function getPayableName();

    public function getPayablePrice();
}