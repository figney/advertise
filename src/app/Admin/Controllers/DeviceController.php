<?php


namespace App\Admin\Controllers;


use App\Models\Device;
use App\Models\DeviceLog;
use Carbon\Carbon;
use DB;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DeviceController extends AdminController
{
    use Base;

    protected $title = "设备";

    protected function grid()
    {
        return Grid::make(new Device(), function (Grid $grid) {

            if (!$this->isAdministrator()) {
                $grid->model()->byChannel(null, false);
            }

            $grid->model()->with(['ips', 'logs', 'user', 'user.wallet', 'user.walletCount', 'user.withdrawOrdersChecking', 'user.vips'])->orderBy('created_at', 'desc');

            $grid->column('imei')->display(function ($imei) {
                return "<a target='_blank' href='" . admin_url('device_logs?imei=' . $imei) . "'>$imei</a>" . " - " . $this->logs->count();
            });
            $grid->column('ip', 'IP / IP数')->display(function () {
                return $this->ip . " - " . $this->ips->count();
            })->filter();
            $grid->column('user')->userInfo();
            $grid->column('is_app')->bool();
            $grid->column('lang')->filter();
            $grid->column('local')->filter();
            $grid->column('version')->filter();
            $grid->column('browser_name')->filter();
            $grid->column('browser_version')->filter();
            $grid->column('brand')->filter();
            $grid->column('model')->filter();
            $grid->column('width')->filter();
            $grid->column('height')->filter();
            $grid->column('os')->filter();
            $grid->column('timezone')->filter();
            $grid->column('channel_id');
            $grid->column('link_id');
            $grid->column('source')->filter();
            $grid->column('source_url')->display(function ($v) {

                return "<span title='$v'>" . Str::limit($v, 40) . "</span>";

            });
            $grid->column('created_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('imei')->width(2);
                $filter->equal('ip')->width(2);
                $filter->where('user_id', function ($q) {
                    $q->where('user_id', (int)$this->input);
                }, '用户ID')->width(2);

                $filter->where('channel_id', function ($q) {
                    $q->where('channel_id', (int)$this->input);
                }, '渠道ID')->width(2);

                $filter->where('link_id', function ($q) {
                    $q->where('link_id', (int)$this->input);
                }, '链接ID')->width(2);

                $filter->where('created_at', function ($q) {
                    $q->where('created_at', '>=', Carbon::make($this->input)->startOfDay());
                    $q->where('created_at', '<=', Carbon::make($this->input)->endOfDay());
                })->date()->width(2);
            });

            $grid->tools(function (Grid\Tools $tools) {
                $tools->append("<a class='btn btn-primary' href='" . admin_url('device_statistics') . "' >数据统计</a>");
            });
            $grid->disablePerPages();
            $grid->disableCreateButton();
            $grid->disableEditButton();
        });
    }

    public function statistics(Content $content)
    {


        $content->body(function (Row $row) {
            $list = Device::query()->raw(function ($collection) {
                return $collection->aggregate([
                    [
                        '$group' => [
                            '_id' => '$os',
                            'count' => ['$sum' => 1],
                        ]
                    ]
                ]);
            })->toArray();
            $list = collect($list)->sortByDesc('count');
            $html = "<div class='card padding'>";
            $html .= "<div class='text-bold'>系统</div>";
            foreach ($list as $item) {
                $html .= "<div>{$item['_id']}：{$item['count']}</div>";
            }
            $html .= "</div>";
            $row->column(1, $html);
            //**************************************************
            $list = Device::query()->raw(function ($collection) {
                return $collection->aggregate([
                    [
                        '$group' => [
                            '_id' => '$local',
                            'count' => ['$sum' => 1],
                        ]
                    ]
                ]);
            })->toArray();
            $list = collect($list)->sortByDesc('count');
            $html = "<div class='card padding'>";
            $html .= "<div class='text-bold'>语言包</div>";
            foreach ($list as $item) {
                $html .= "<div>{$item['_id']}：{$item['count']}</div>";
            }
            $html .= "</div>";
            $row->column(1, $html);
            //**************************************************
            $list = Device::query()->raw(function ($collection) {
                return $collection->aggregate([
                    [
                        '$group' => [
                            '_id' => '$lang',
                            'count' => ['$sum' => 1],
                        ]
                    ]
                ]);
            })->toArray();
            $html = "<div class='card padding'>";
            $html .= "<div class='text-bold'>语言</div>";
            $list = collect($list)->sortByDesc('count');
            foreach ($list as $item) {
                $html .= "<div>{$item['_id']}：{$item['count']}</div>";
            }
            $html .= "</div>";
            $row->column(1, $html);

            //**************************************************
            $list = Device::query()->raw(function ($collection) {
                return $collection->aggregate([
                    [
                        '$group' => [
                            '_id' => '$browser_name',
                            'count' => ['$sum' => 1],
                        ]
                    ]
                ]);
            })->toArray();
            $html = "<div class='card padding'>";
            $html .= "<div class='text-bold'>浏览器</div>";
            $list = collect($list)->sortByDesc('count');
            foreach ($list as $item) {
                $html .= "<div>{$item['_id']}：{$item['count']}</div>";
            }
            $html .= "</div>";
            $row->column(2, $html);
            //**************************************************
            $list = Device::query()->raw(function ($collection) {
                return $collection->aggregate([
                    [
                        '$group' => [
                            '_id' => '$brand',
                            'count' => ['$sum' => 1],
                        ]
                    ]
                ]);
            })->toArray();
            $list = collect($list)->sortByDesc('count');
            $html = "<div class='card padding'>";
            $html .= "<div class='text-bold'>设备品牌</div>";
            foreach ($list as $item) {
                $html .= "<div>{$item['_id']}：{$item['count']}</div>";
            }
            $html .= "</div>";
            $row->column(1, $html);
            //**************************************************
            $list = Device::query()->raw(function ($collection) {
                return $collection->aggregate([
                    [
                        '$group' => [
                            '_id' => '$model',
                            'count' => ['$sum' => 1],
                        ]
                    ]
                ]);
            })->toArray();
            $list = collect($list)->sortByDesc('count');
            $html = "<div class='card padding'>";
            $html .= "<div class='text-bold'>设备型号</div>";
            foreach ($list as $item) {
                $html .= "<div>{$item['_id']}：{$item['count']}</div>";
            }
            $html .= "</div>";
            $row->column(2, $html);
            //**************************************************


            $list = Device::query()->raw(function ($collection) {
                return $collection->aggregate([
                    [
                        '$group' => [
                            '_id' => ['width' => '$width', 'height' => '$height'],
                            'count' => ['$sum' => 1],
                        ]
                    ]
                ]);
            })->toArray();
            $list = collect($list)->sortByDesc('count');
            $html = "<div class='card padding'>";
            $html .= "<div class='text-bold'>分辨率</div>";
            foreach ($list as $item) {
                $html .= "<div>{$item['_id']['width']} x {$item['_id']['height']}：{$item['count']}</div>";
            }
            $html .= "</div>";
            $row->column(2, $html);
            //**************************************************
        });


        return $content;

    }

    protected function form()
    {
        return Form::make(new Device(), function (Form $form) {
        });
    }

}
