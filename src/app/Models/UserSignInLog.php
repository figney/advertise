<?php

namespace App\Models;

use App\Models\Traits\AdminDataScope;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;


class UserSignInLog extends Model
{
    use HasFactory, HasDateTimeFormatter,AdminDataScope;

    protected $guarded = [];

    protected $table = "user_sign_logs";

    protected $connection = "mongodb";


}
