<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;

/**
 * @property int continuous 连续签到
 * @package App\Models
 */
class UserSignIn extends Model
{
    use HasFactory, HasDateTimeFormatter;

    protected $guarded = [];

    protected $table = "user_sign";

    protected $connection = "mongodb";

    protected $dates = ['last_time'];


}
