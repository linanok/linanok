<?php

namespace App\Providers;

use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Intentionally left blank: Horizon authorization is handled
     * in {@see \App\Providers\AuthServiceProvider} instead of here.
     */
    protected function gate() {}
}
