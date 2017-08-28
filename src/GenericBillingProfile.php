<?php

namespace Krisnasw\Faspay;

class GenericBillingProfile implements BillingProfileInterface
{
    public function description()
    {
        return 'Transaksi Pembayaran';  
    }

    public function generate(Payment $payment)
    {
        return str_random(15);
    }
}