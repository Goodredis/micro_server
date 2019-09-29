<?php

namespace App\Utils\Facade;

use Illuminate\Support\Facades\Facade;

class Lunar extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Lunar';
    }
}