<?php


namespace App\Admin\Metrics;


use App\Models\UserRechargeOrder;
use Carbon\Carbon;
use Dcat\Admin\Widgets\Metrics\Card;
use Illuminate\Http\Request;

class MetricRechargeChannel extends Card
{

    protected $height = 355;

    protected function init()
    {
        parent::init();
        $this->title("充值渠道统计");
        $this->dropdown([
            'today' => '今日',
            'yesterday' => '昨日',
            'all' => '全部',
        ]);
    }

    public function handle(Request $request)
    {
        $option = $request->get('option', 'today');

        $orm = UserRechargeOrder::query()
            ->noTester()->byChannel()
            ->with(['rechargeChannel', 'rechargeChannelItem']);

        if ($option == "today") {
            $orm->where('created_at', '>', Carbon::today());
        }
        if ($option == "yesterday") {
            $orm->whereBetween('created_at', [Carbon::yesterday(), Carbon::yesterday()->endOfDay()]);
        }

        $orm = $p_orm = $orm->groupBy(['recharge_channel_id', 'recharge_channel_item_id'])->select(['recharge_channel_id', 'recharge_channel_item_id', 'order_status', \DB::raw('count(*) as count')]);
        $list = $orm->get();
        $pay_list = $p_orm->pay()->get();

        $html = "<div class='row'>
<div class='col-lg-3'>支付渠道</div>
<div class='col-lg-3'> 渠道选项 </div>
<div class='col-lg-2'>提交订单</div>
<div class='col-lg-2'>支付订单 </div>
<div class='col-lg-2'>成功率</div>
</div>";


        foreach ($list as $key => $item) {
            $pay_count = 0;
            $count = $item->count;
            $pay_item = $pay_list->filter(function ($x) use ($item) {
                return $x->recharge_channel_id == $item->recharge_channel_id && $x->recharge_channel_item_id == $item->recharge_channel_item_id;
            })->first();
            if ($pay_item) $pay_count = $pay_item->count;

            $cgl = $count > 0 ? (round($pay_count / $count, 4) * 100) : 0;

            $html .= "<div class='row margin-top-xs'>
<div class='col-lg-3'>{$item->rechargeChannel->name} </div>
<div class='col-lg-3'> {$item->rechargeChannelItem?->name} </div>
<div class='col-lg-2'>$count</div>
<div class='col-lg-2'>$pay_count </div>
<div class='col-lg-2'>$cgl %</div>
</div>";
        }

        $content = <<<HTML
<div class="padding-lr">$html</div>
HTML;


        $this->content($content);

    }

}
