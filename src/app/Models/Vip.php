<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;


class Vip extends Model
{
    use HasDateTimeFormatter;

    protected $connection = "mysql";

    protected $casts = [
        'forever' => 'boolean',
        'describe' => 'json',
        'title' => 'json',
        'attrs' => 'json',
        'privilege' => 'json',
        'son_buy_vip_commission_config' => 'json',
        'son_earnings_commission_config' => 'json',
        'dev_config' => 'json',
        'task_num_money_list' => 'json',
    ];

    public function userVip()
    {
        return $this->hasMany(UserVip::class);
    }

    public function getDayMoneyDataAttribute()
    {
        return collect($this->task_num_money_list)->map(function ($item) {

            $item['day'] = (int)$item['day'];
            $item['money'] = (float)$item['money'];

            return $item;
        })->toArray();
    }

    protected static function booted()
    {
        self::deleting(function (Vip $vip) {
            abort_if($vip->userVip()->count() > 0, 400, "当前套餐已有用户购买，无法删除");
        });
    }

}
