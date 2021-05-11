<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;


class Language extends Model
{
    use HasDateTimeFormatter,Cachable;

    protected $casts = [
        'required' => 'bool'
    ];
}
