<?php

namespace App\Console\Commands;

use App\Enums\PlatformType;
use App\Enums\QueueType;
use App\Enums\RechargeChannelType;
use App\Jobs\GetPayZjrAddress;
use App\Models\CoinAddress;
use App\Models\RechargeChannel;
use Illuminate\Console\Command;

class GetCoinAddress extends Command
{

    protected $signature = 'command:GetCoinAddress';


    protected $description = '获取数字货币地址';


    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        return;

        $fz = 50;
        $rc = RechargeChannel::query()->where('slug', PlatformType::LaoSun)->where('type', RechargeChannelType::USDT_TRC20)->where('status', 1)->first();
        //获取当前渠道可用地址
        $count = CoinAddress::query()->disableCache()->where('recharge_channel_id', $rc->id)->where('user_id', 0)->count();
        $this->info("可用地址：" . $count);
        if ($count < $fz) {
            \Log::info("获取USDT地址:" . ($fz - $count));
            for ($i = $count; $i < $fz; $i++) {
                dispatch(new GetPayZjrAddress($rc))->onQueue(QueueType::request);
            }
        }


    }
}
