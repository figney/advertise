<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;


class AdTaskData extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'ad_task_data';

    public $timestamps = false;

    protected $connection = "mysql";

    protected $casts = [
        'title' => 'json',
        'describe' => 'json',
        'content' => 'json',
        'share_content' => 'json'
    ];

}
