<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;


class UserAdTaskLog extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'user_ad_task_log';

    protected $connection = "mysql";

    protected $guarded = [];


}
