<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;

class UserInviteAward extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'user_invite_award';
    protected $guarded = [];

}
