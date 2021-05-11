<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Jenssegers\Mongodb\Eloquent\Model;


class UserWithdrawOrderCheckData extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'user_withdraw_order_check_data';

    protected $connection = "mongodb";

    protected $guarded = [];

}
