<?php

namespace Krisnasw\Faspay;

interface BillingProfileInterface
{
    public function description();
    
    public function generate(Payment $payment);
}