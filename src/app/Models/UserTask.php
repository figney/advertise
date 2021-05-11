<?php

namespace App\Models;

use App\Enums\UserHookType;
use App\Enums\WalletType;
use Carbon\Carbon;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;


class UserTask extends Model
{
    use HasDateTimeFormatter;


    protected $table = 'user_tasks';

    protected $guarded = [];


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function getDayAchieveAttribute()
    {

        if ($this->last_achieve_time) {
            return Carbon::make($this->last_achieve_time)->gte(Carbon::today());
        }
        if ($this->achieve_time && $this->hook !== UserHookType::Invite) {
            return Carbon::make($this->achieve_time)->gte(Carbon::today());
        }
        return false;

    }


}
