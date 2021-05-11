<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Jenssegers\Mongodb\Eloquent\Model;


class UserAwardRecord extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'user_award_record';

    protected $connection = "mongodb";

    protected $guarded = [];

    public function son()
    {
        return $this->belongsTo(User::class, 'son_user_id');
    }

}
