<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;


class UserVip extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'user_vips';
    protected $connection = "mysql";

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vip()
    {
        return $this->belongsTo(Vip::class);
    }


    /**
     * 用户每天可接广告数量
     * @return int
     */
    public function getUserAdTaskDayNum(int $level): int
    {

        return $this->vips();
    }
}
