<?php

namespace App\Admin\Metrics;


use App\Models\Device;
use App\Models\User;
use Carbon\Carbon;
use Dcat\Admin\Widgets\Metrics\Line;
use Illuminate\Http\Request;

class MetricUsers extends Line
{

    protected $yesterday_time_count_user;
    protected $yesterday_time_count_device;

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
        //'colors' => ['#5E35B1', '#283593'],
        'stroke' => [
            'width' => 2.5,
            'curve' => 'smooth',
        ],
    ];

    protected function init()
    {
        parent::init();
        // 标题
        $this->title('用户 / 设备');
        // 设置下拉选项
        $this->dropdown([
            'today' => '今日',
            'yesterday' => '昨日',
            '7' => '7天',
            '30' => '30天',
            'all' => '全部',
        ]);

        $this->chartOption('tooltip.x.show', true);

        /*$data = [];
        $data[0]['name'] = "用户";
        $data[1]['name'] = "设备";

        $label = collect();
        for ($i = 6; $i >= 0; $i--) {
            $data[0]['data'][] =
                User::query()
                    ->byChannel()
                    ->whereBetween('created_at', [Carbon::today()->addDays(-$i)->startOfDay(), Carbon::today()->addDays(-$i)->endOfDay()])
                    ->count();

            $data[1]['data'][] = Device::query()
                ->byChannel()
                ->whereBetween('created_at', [Carbon::today()->addDays(-$i)->startOfDay(), Carbon::today()->addDays(-$i)->endOfDay()])
                ->count();


            $label->add(Carbon::today()->addDays(-$i)->startOfDay()->toDateString());
        }
        $this->withChart($data, $label->toArray());*/

    }

    public function handle(Request $request)
    {
        //昨日此时
        $this->yesterday_time_count_user = User::query()->whereBetween('created_at', [Carbon::yesterday(), now()->addDays(-1)])->byChannel()->count();
        $this->yesterday_time_count_device = Device::query()->whereBetween('created_at', [Carbon::yesterday(), now()->addDays(-1)])->byChannel()->count();


        $user_orm = User::query()->byChannel();
        $device_orm = Device::query()->byChannel();
        switch ($request->get('option')) {
            default:
                $user_orm->where('created_at', '>=', Carbon::today());
                $device_orm->where('created_at', '>=', Carbon::today());
                break;
            case 'yesterday':
                $user_orm->whereBetween('created_at', [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()]);
                $device_orm->whereBetween('created_at', [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()]);
                break;
            case '7':
                $user_orm->where('created_at', '>=', Carbon::today()->addDays(-7));
                $device_orm->where('created_at', '>=', Carbon::today()->addDays(-7));
                break;
            case '30':
                $user_orm->where('created_at', '>=', Carbon::today()->addDays(-30));
                $device_orm->where('created_at', '>=', Carbon::today()->addDays(-30));
                break;
            case 'all':
                break;
        }

        $count_user = $user_orm->count();
        $count_device = $device_orm->count();


        $this->withContent($count_user, $count_device);


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

    public function withContent($count_user, $count_device)
    {

        $zcl = $count_device > 0 ? round($count_user / $count_device, 2) * 100 : 0;

        $count_user = number_format($count_user);
        $count_device = number_format($count_device);

        return $this->content(
            <<<HTML
<div class="d-flex justify-content-between align-items-center mt-1" style="margin-bottom: 2px">
    <h2 class="ml-1 font-lg-1">{$count_user} / {$count_device}</h2>
    <span class="mb-0 mr-1 text-80">昨日此时：{$this->yesterday_time_count_user} / {$this->yesterday_time_count_device}</span>
</div>
<div class="padding-lr">注册率：$zcl%</div>
HTML
        );
    }

}
