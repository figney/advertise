<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Jenssegers\Mongodb\Eloquent\Model;


class UserTaskLog extends Model
{
    use HasDateTimeFormatter;

    protected $connection = "mongodb";


    protected $table = 'user_task_logs';

    protected $guarded = [];

    protected $dates = ['achieve_time','last_time','next_time'];


}
