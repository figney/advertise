<?php

namespace App\Services;

use App\Traits\AppBase;

class BaseService
{
    use AppBase;

    public static function make()
    {
        return new static();
    }

}
