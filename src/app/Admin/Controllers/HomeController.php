<?php

namespace App\Admin\Controllers;

use App\Admin\Metrics\MetricChannel;
use App\Admin\Metrics\MetricRecharge;
use App\Admin\Metrics\MetricRechargeChannel;
use App\Admin\Metrics\MetricRechargePayRate;
use App\Admin\Metrics\MetricSumWallet;
use App\Admin\Metrics\MetricUsers;
use App\Admin\Metrics\MetricWithdraw;
use App\Http\Controllers\Controller;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;

class HomeController extends Controller
{
    use Base;

    public function index(Content $content)
    {
        if (\Admin::user()->cannot('查看数据统计')) return $content->body("欢迎");

        $rule = \Admin::user()->roles()->pluck('name')->join(',');

        $C_IDS = collect($this->getChannelIds())->join(',');
        if ($this->isAdministrator()) $C_IDS = "全部";

        $content
            ->body("<div class='fs-25 text-bold margin-bottom'>实时数据 - $rule ID: $C_IDS</div>")
            ->body(view('admin.sync-title'))
            ->body(function (Row $row) {
                $row->column(3, new MetricUsers());
                $row->column(3, new MetricRechargePayRate());
            })
            ->body(function (Row $row) {

                $row->column(6, new MetricRecharge());
                $row->column(6, new MetricWithdraw());

            })
            ->body("<div class='fs-25 text-bold padding-bottom'>统计数据</div>")
            ->body(function (Row $row) {
                $row->column(4, new MetricSumWallet());
                $row->column(4, new MetricRechargeChannel());
            });

        if (\Admin::user()->isAdministrator()) {
            $content->body(function (Row $row) {
                $row->column(12, new MetricChannel());
            });
        }

        return $content;
    }
}
