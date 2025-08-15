<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class HorizonServiceProvider extends ServiceProvider
{
    /**
     * Intentionally left blank: Horizon authorization is handled
     * in {@see \App\Providers\AuthServiceProvider} instead of here.
     */
    protected function gate() {}
}
