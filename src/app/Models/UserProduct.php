<?php

namespace App\Models;

use App\Models\Traits\AdminDataScope;
use Carbon\Carbon;
use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class UserProduct extends Model
{
    use HasDateTimeFormatter,AdminDataScope;

    protected $table = 'user_products';
    protected $guarded = [];

    protected $casts = [
        'is_day_account' => 'bool',
        'is_over' => 'bool',
        'status' => 'bool',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function dayData()
    {

        //购买时间
        $buy_time = $this->created_at;


        //结束时间
        $over_time = $this->over_day;



        //总收益
        $fee = $this->amount * ($this->day_rate / 100) * $this->day_cycle;

        //总秒数
        $all_s = Carbon::make($over_time)->floatDiffInSeconds(Carbon::make($buy_time));

        //每一秒收益
        $s_fee = $fee / $all_s;

        //今天能走多少秒
        $day_s = 86400;
        //当前走了多少秒
        $this_s = now()->floatDiffInSeconds(Carbon::today());


        //如果是今天购买
        if (Carbon::make($buy_time)->isToday()) {
            $day_s = Carbon::tomorrow()->floatDiffInSeconds(Carbon::make($buy_time));

            $this_s = now()->floatDiffInSeconds(Carbon::make($buy_time));
        }
        //如果是今天结束
        if (Carbon::make($over_time)->isToday()) {
            $day_s = Carbon::make($over_time)->floatDiffInSeconds(Carbon::today());
        }
        //今天预估收益
        $day_fee = $day_s * $s_fee;

        //当前开始收益
        $this_fee = $this_s * $s_fee;


        return [

            'day_fee' => $day_fee,//今天预估收益  结束数字
            'this_fee' => $this_fee,//当前开始收益  开始数字
            's_fee'=>$s_fee,
            //'day_s' => $day_s,
            'this_s' => $this_s,
            'residue_s' => intval($day_s - $this_s),//剩余秒数  动画秒数
        ];


    }

}
