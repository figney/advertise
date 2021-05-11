<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;


class UserInviteLog extends Model
{
    use HasFactory, HasDateTimeFormatter;

    protected $guarded = [];

    protected $table = "user_invite_logs";

    protected $connection = "mongodb";


}
