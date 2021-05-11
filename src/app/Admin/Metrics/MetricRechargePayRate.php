<?php

namespace App\Admin\Metrics;


use App\Models\User;
use App\Models\UserRechargeOrder;
use Carbon\Carbon;
use Dcat\Admin\Widgets\Metrics\Line;
use Illuminate\Http\Request;

class MetricRechargePayRate extends Line
{

    protected mixed $yesterday_time_count_1;
    protected mixed $yesterday_time_count_2;

    protected $chartOptions = [
        'chart' => [
            'type' => 'area',
            'toolbar' => [
                'show' => false,
            ],
            'sparkline' => [
                'enabled' => true,
            ],
            'grid' => [
                'show' => false,
                'padding' => [
                    'left' => 0,
                    'right' => 0,
                ],
            ],
        ],
        'dataLabels' => [
            'enabled' => false,
        ],
        //'colors' => ['#0288D1', '#00796B'],
        'stroke' => [
            'width' => 2.5,
            'curve' => 'smooth',
        ],
    ];

    protected function init()
    {
        parent::init();
        // 标题
        $this->title('充值订单');
        // 设置下拉选项
        $this->dropdown([
            'today' => '今日',
            'yesterday' => '昨日',
            '7' => '7天',
            '30' => '30天',
            'all' => '全部',
        ]);

    }

    public function handle(Request $request)
    {
        //充值率 =  支付用户数 /用户数
        //支付成功率 = 支付订单数 / 订单数

        $option = $request->get('option', 'today');

        switch ($option) {
            default:
                $time = Carbon::today();
                //用户数
                $u = User::query()->where('created_at', '>=', $time)->byChannel()->count();
                //支付用户数
                $pu = User::query()->where('created_at', '>=', $time)->where('recharge_count', '>', 0)->byChannel()->count();
                //订单数
                $d = UserRechargeOrder::query()->where('created_at', '>=', $time)->byChannel()->count();
                $pd = UserRechargeOrder::query()->where('pay_time', '>=', $time)->pay()->byChannel()->count();

                $czyh = UserRechargeOrder::query()->where('created_at', '>=', $time)->byChannel()->pay()->get(['user_id'])->groupBy('user_id')->count();
                break;
            case 'yesterday':
                $time = [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()];
                //用户数
                $u = User::query()->whereBetween('created_at', $time)->byChannel()->count();
                //支付用户数
                $pu = User::query()->whereBetween('created_at', $time)->where('recharge_count', '>', 0)->byChannel()->count();
                //订单数
                $d = UserRechargeOrder::query()->whereBetween('created_at', $time)->byChannel()->count();
                $pd = UserRechargeOrder::query()->whereBetween('pay_time', $time)->pay()->byChannel()->count();

                $czyh = UserRechargeOrder::query()->whereBetween('created_at', $time)->byChannel()->pay()->get(['user_id'])->groupBy('user_id')->count();
                break;
            case '7':

                $time = Carbon::today()->addDays(-7);
                //用户数
                $u = User::query()->where('created_at', '>=', $time)->byChannel()->count();
                //支付用户数
                $pu = User::query()->where('created_at', '>=', $time)->where('recharge_count', '>', 0)->byChannel()->count();
                //订单数
                $d = UserRechargeOrder::query()->where('created_at', '>=', $time)->byChannel()->count();
                $pd = UserRechargeOrder::query()->where('pay_time', '>=', $time)->pay()->byChannel()->count();
                $czyh = UserRechargeOrder::query()->where('created_at', '>=', $time)->byChannel()->pay()->get(['user_id'])->groupBy('user_id')->count();
                break;
            case '30':
                $time = Carbon::today()->addDays(-30);
                //用户数
                $u = User::query()->where('created_at', '>=', $time)->byChannel()->count();
                //支付用户数
                $pu = User::query()->where('created_at', '>=', $time)->where('recharge_count', '>', 0)->byChannel()->count();
                //订单数
                $d = UserRechargeOrder::query()->where('created_at', '>=', $time)->byChannel()->count();
                $pd = UserRechargeOrder::query()->where('pay_time', '>=', $time)->pay()->byChannel()->count();
                $czyh = UserRechargeOrder::query()->where('created_at', '>=', $time)->byChannel()->pay()->get(['user_id'])->groupBy('user_id')->count();
                break;
            case 'all':
                $time = Carbon::today()->addDays(-3000);
                //用户数
                $u = User::query()->where('created_at', '>=', $time)->byChannel()->count();
                //支付用户数
                $pu = User::query()->where('created_at', '>=', $time)->where('recharge_count', '>', 0)->byChannel()->count();
                //订单数
                $d = UserRechargeOrder::query()->where('created_at', '>=', $time)->byChannel()->count();
                $pd = UserRechargeOrder::query()->where('pay_time', '>=', $time)->pay()->byChannel()->count();
                $czyh = UserRechargeOrder::query()->where('created_at', '>=', $time)->byChannel()->pay()->get(['user_id'])->groupBy('user_id')->count();
                break;
        }

        $ytime = [Carbon::yesterday()->startOfDay(), Carbon::now()->addDays(-1)];
        //用户数
        $yu = User::query()->whereBetween('created_at', $ytime)->byChannel()->count();
        //支付用户数
        $ypu = User::query()->whereBetween('created_at', $ytime)->where('recharge_count', '>', 0)->byChannel()->count();
        //订单数
        $yd = UserRechargeOrder::query()->whereBetween('created_at', $ytime)->byChannel()->count();
        $ypd = UserRechargeOrder::query()->whereBetween('pay_time', $ytime)->pay()->byChannel()->count();

        $data_1 = $u > 0 ? round($pu / $u, 4) * 100 : 0;
        $data_2 = $d > 0 ? round($pd / $d, 4) * 100 : 0;

        $ydata_1 = $yu > 0 ? round($ypu / $yu, 4) * 100 : 0;
        $ydata_2 = $yd > 0 ? round($ypd / $yd, 4) * 100 : 0;

        $display = $option == 'today' ? '' : 'none';

        $content = <<<HTML
<div class="padding-lr flex">
<div class="flex-sub">

<div class="fs-12">
<div><i class="fa fa-circle text-primary"></i> 注册用户数：{$u}</div>
<div class="margin-top-xs"><i class="fa fa-circle text-primary"></i> 注册充值用户：{$pu}</div>
<div class="margin-top-xs"><i class="fa fa-circle text-primary"></i> 充值用户：{$czyh}</div>
<div class="margin-top-xs"><i class="fa fa-circle text-primary"></i> 充值率：{$data_1}%</div>
<div class="margin-top-xs" style="display: {$display}"><i class="fa fa-circle text-primary"></i> 昨日此时：{$ydata_1}%</div>
</div>
</div>

<div class="flex-sub">

<div class="fs-12">
<div><i class="fa fa-circle text-success"></i> 订单数：{$d}</div>
<div class="margin-top-xs"><i class="fa fa-circle text-success"></i> 支付订单数：{$pd}</div>
<div class="margin-top-xs"><i class="fa fa-circle text-success"></i> 成功率：{$data_2}%</div>
<div class="margin-top-xs" style="display: {$display}"><i class="fa fa-circle text-success"></i> 昨日此时：{$ydata_2}%</div>
</div>
</div>

</div>
HTML;


        $this->content($content);

    }


}
