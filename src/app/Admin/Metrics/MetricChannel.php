<?php


namespace App\Admin\Metrics;


use App\Enums\WalletType;
use App\Models\Channel;
use App\Models\Device;
use App\Models\User;
use App\Models\UserRechargeOrder;
use Carbon\Carbon;
use Dcat\Admin\Widgets\Metrics\Card;
use Illuminate\Http\Request;

class MetricChannel extends Card
{

    protected function init()
    {
        parent::init();

        $this->title("渠道统计");

        $this->dropdown([
            'select' => '选择时间',
            'today' => '今日',
            'yesterday' => '昨日',
            'all' => '全部',
        ]);
    }


    public function handle(Request $request)
    {
        $option = $request->get('option');

        if (!$option || $option == "select") {
            $this->content("<div class='padding-lr margin-top'>
<div class='alert alert-warning'>在右上角选择时间</div>
</div>");
            return;
        }

        $channels = Channel::query()->get();

        $html = "<div class='row text-bold'>
<div class='col-lg-1'>渠道</div>
<div class='col-lg-1'>设备</div>
<div class='col-lg-1'>用户</div>
<div class='col-lg-1'>注册率</div>
<div class='col-lg-1'>提交订单人数</div>
<div class='col-lg-1'>充值人数</div>
<div class='col-lg-1'>充值率/成功率</div>
<div class='col-lg-2'>充值金额</div>
<div class='col-lg-1'>人均充值 </div>
</div>";

        foreach ($channels as $channel) {
            $channel_ids = [$channel->id];
            if ($channel->id == 1) {
                $channel_ids = [0, $channel->id];
            }

            //设备
            $device_orm = Device::query()->whereIn('channel_id', $channel_ids);
            if ($option == "today") {
                $device_orm->where('created_at', '>', Carbon::today());
            }
            if ($option == "yesterday") {
                $device_orm->whereBetween('created_at', [Carbon::yesterday(), Carbon::yesterday()->endOfDay()]);
            }

            $device_count = $device_orm->count();

            //用户
            $user_orm = User::query()->whereIn('channel_id', $channel_ids);
            if ($option == "today") {
                $user_orm->where('created_at', '>', Carbon::today());
            }
            if ($option == "yesterday") {
                $user_orm->whereBetween('created_at', [Carbon::yesterday(), Carbon::yesterday()->endOfDay()]);
            }

            $user_count = $user_orm->noTester()->count();

            //注册率
            $reg_rate = $device_count > 0 ? (round($user_count / $device_count, 3) * 100) : 0;


            //提交订单人数

            $tjddrs = $this->getUserRechargeOrderModel($option, $channel_ids)->get(['user_id'])->groupBy('user_id')->count();



            //充值人数

            $czrs = $this->getUserRechargeOrderModel($option, $channel_ids)->pay()->get(['user_id'])->groupBy('user_id')->count();


            //充值率
            $cz_rate = $user_count > 0 ? (round($czrs / $user_count, 3) * 100) : 0;
            //充值成功率
            $czcg_rate = $tjddrs > 0 ? (round($czrs / $tjddrs, 3) * 100) : 0;
            //充值金额

            $czje_b = $this->getUserRechargeOrderModel($option, $channel_ids)->pay()->where('wallet_type', WalletType::balance)->sum('amount');
            $czje_u = $this->getUserRechargeOrderModel($option, $channel_ids)->pay()->where('wallet_type', WalletType::usdt)->sum('amount');

            $czje = $czje_b + UsdtToBalance($czje_u);

            //人均充值
            $rjcz = $user_count > 0 ? $czje / $user_count : 0;


            $html .= "<div class='row margin-top'>";

            $html .= "<div class='col-lg-1'>$channel->name</div>";
            $html .= "<div class='col-lg-1'>$device_count</div>";
            $html .= "<div class='col-lg-1'>$user_count</div>";
            $html .= "<div class='col-lg-1'>$reg_rate %</div>";
            $html .= "<div class='col-lg-1'>$tjddrs</div>";
            $html .= "<div class='col-lg-1'>$czrs</div>";
            $html .= "<div class='col-lg-1'>$cz_rate % / $czcg_rate %</div>";
            $html .= "<div class='col-lg-2'>" . number_format($czje) . "<span class='fs-12 margin-left-xs text-warning'>包含:".floatval($czje_u)." U</span></div>";
            $html .= "<div class='col-lg-1'>" . number_format($rjcz) . "</div>";
            $html .= "</div>";
        }


        $content = <<<HTML
<div class="padding-lr padding-bottom">
$html
</div>
HTML;


        $this->content($content);
    }


    private function getUserRechargeOrderModel($option, $channel_ids)
    {
        $orm = UserRechargeOrder::query()->whereIn('channel_id', $channel_ids);

        if ($option == "today") {
            $orm->where('created_at', '>', Carbon::today());
        }
        if ($option == "yesterday") {
            $orm->whereBetween('created_at', [Carbon::yesterday(), Carbon::yesterday()->endOfDay()]);
        }

        return $orm;
    }

}
