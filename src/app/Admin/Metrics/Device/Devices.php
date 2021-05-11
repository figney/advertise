<?php

namespace App\Admin\Metrics\Device;

use App\Models\Device;
use Carbon\Carbon;
use Dcat\Admin\Widgets\Metrics\Line;
use Illuminate\Http\Request;

class Devices extends Line
{
    /**
     * 初始化卡片内容
     *
     * @return void
     */
    protected function init()
    {
        parent::init();

        $this->title('设备数量');

        $this->chartOption('tooltip.x.show', true);

        $this->dropdown([
            1 => '今天',
            7 => '7天内',
        ]);
    }


    /**
     * 处理请求
     *
     * @param Request $request
     *
     * @return mixed|void
     */
    public function handle(Request $request)
    {
        $option = $request->input('option', 1);


        switch ($option) {
            case 1:
                $count = Device::query()->where('created_at', '>=', Carbon::today())->count();
                $this->withContent(number_format($count));
                break;
            case 7:
                $count = Device::query()->where('created_at', '>=', Carbon::today()->addDays(-6))->count();
                $this->withContent(number_format($count));
                $data = collect();
                $label = collect();
                for ($i = 6; $i >= 0; $i--) {

                    $d_count = Device::query()
                        ->whereBetween('created_at', [Carbon::today()->addDays(-$i)->startOfDay(), Carbon::today()->addDays(-$i)->endOfDay()])
                        ->count();
                    $data->add($d_count);
                    $label->add(Carbon::today()->addDays(-$i)->startOfDay()->toDateString());
                }
                $this->withChart($data->toArray(), $label->toArray());
                break;
        }


    }

    /**
     * 设置图表数据.
     *
     * @param array $data
     * @param array $label
     * @return $this
     */
    public function withChart(array $data, array $label): Devices
    {


        return $this->chart([
            'series' => [
                [
                    'name' => $this->title,
                    'data' => $data,
                ],
            ],
            'xaxis' => [
                'categories' => $label
            ]
        ]);
    }

    /**
     * 设置卡片内容.
     *
     * @param string $content
     *
     * @return $this
     */
    public function withContent($content)
    {
        return $this->content(
            <<<HTML
<div class="d-flex justify-content-between align-items-center mt-1" style="margin-bottom: 2px">
    <h2 class="ml-1 font-lg-1">{$content}</h2>
    <span class="mb-0 mr-1 text-80">{$this->title}</span>
</div>
HTML
        );
    }
}
