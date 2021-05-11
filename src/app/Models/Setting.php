<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;


class Setting extends Model
{
    use Cachable;

    const CACHE_TAG = "APP-SETTING";


    public $timestamps = false;


    protected $casts = [
        'usdt_money_rate' => 'float',
        'rmb_money_rate' => 'float',
        'usdt_to_money_min' => 'float',
        'money_to_usdt_min' => 'float',
        'country_code' => 'array',
        'recharge_select' => 'array',
        'show_suffix' => 'bool',
        'is_sms_reg' => 'bool',
        'close_recharge_describe' => 'json',
        'close_withdraw_describe' => 'json',
        'first_recharge_select' => 'json',
        'google_check_domains' => 'json',
    ];


    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }


    protected static function booted()
    {


        //拦截删除
        static::deleting(function ($item) {
            abort(400, "无法删除");
        });
    }

}
