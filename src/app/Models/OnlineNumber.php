<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Jenssegers\Mongodb\Eloquent\Model;


class OnlineNumber extends Model
{
    use HasDateTimeFormatter;

    protected $connection = "mongodb";

    protected $table = 'online_numbers';

    protected $guarded = [];


}
