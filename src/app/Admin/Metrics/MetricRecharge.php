<?php

namespace App\Admin\Metrics;


use App\Enums\RechargeChannelType;
use App\Enums\WalletType;
use App\Models\RechargeChannel;
use App\Models\UserRechargeOrder;
use Carbon\Carbon;
use Dcat\Admin\Widgets\Metrics\Line;
use Illuminate\Http\Request;

class MetricRecharge extends Line
{

    protected $yesterday_time_count_f;
    protected $yesterday_time_count_u;

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
        $this->title('充值 法币 / USDT');
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
        //昨日此时
        $this->yesterday_time_count_f = (float)UserRechargeOrder::query()->whereBetween('pay_time', [Carbon::yesterday(), now()->addDays(-1)])->where('wallet_type', WalletType::balance)->pay()->byChannel()->sum('amount');
        $this->yesterday_time_count_u = (float)UserRechargeOrder::query()->whereBetween('pay_time', [Carbon::yesterday(), now()->addDays(-1)])->where('wallet_type', WalletType::usdt)->pay()->byChannel()->sum('amount');


        $orm = UserRechargeOrder::query()->pay()->byChannel();
        $orm_1 = UserRechargeOrder::query()->pay()->byChannel();

        $ids = RechargeChannel::query()->where('type', RechargeChannelType::TransferAccounts)->pluck('id');

        switch ($request->get('option')) {
            default:
                $orm->where('pay_time', '>=', Carbon::today());
                $orm_1->where('pay_time', '>=', Carbon::today());
                break;
            case 'yesterday':
                $orm->whereBetween('pay_time', [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()]);
                $orm_1->whereBetween('pay_time', [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()]);
                break;
            case '7':
                $orm->where('pay_time', '>=', Carbon::today()->addDays(-7));
                $orm_1->where('pay_time', '>=', Carbon::today()->addDays(-7));
                break;
            case '30':
                $orm->where('pay_time', '>=', Carbon::today()->addDays(-30));
                $orm_1->where('pay_time', '>=', Carbon::today()->addDays(-30));
                break;
            case 'all':
                break;
        }

        $orm_trf = $orm;


        $count_f = $orm->where('wallet_type', WalletType::balance)->sum('amount');

        $count_u = $orm_1->where('wallet_type', WalletType::usdt)->sum('amount');

        $count_trf = $orm_trf->whereIn('recharge_channel_id', $ids)->sum('amount');


        $this->withContent(round($count_f, 2), round($count_u, 2), round($count_trf, 2));


    }

    public function withChart(array $data, array $label)
    {


        return $this->chart([
            'series' => $data,
            'xaxis' => [
                'categories' => $label
            ]
        ]);
    }

    public function withContent($count_user, $count_device,$trf)
    {
        $count_user = ShowMoney($count_user);
        $count_device = ShowMoney($count_device,true);
        $trf = ShowMoney($trf);

        $zrcs = ShowMoney($this->yesterday_time_count_f);
        $zrcs_u = ShowMoney($this->yesterday_time_count_u,true);

        return $this->content(
            <<<HTML
<div class="d-flex justify-content-between align-items-center mt-1" style="margin-bottom: 2px">
    <div class="flex padding-lr"><div>法币(包括转账)：{$count_user} </div>  <div class="margin-left-lg">USDT：{$count_device}</div> <div class="margin-left-xl text-80">转账充值：$trf</div></div>
    <span class="mb-0 margin-right flex text-80"><div>昨日此时：{$zrcs}</div>  <div class="margin-left">USDT：$zrcs_u</div></span>
</div>

HTML
        );
    }

}
