<?php

namespace Krisnasw\Faspay\Facades;

use Illuminate\Support\Facades\Facade;

class Faspay extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'faspay'; }
}