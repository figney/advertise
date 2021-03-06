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

        $content
            ->body("<div class='fs-25 text-bold margin-bottom'>实时数据 - $rule </div>")
            ->body(view('admin.sync-title'))
            ->body(function (Row $row) {
                $row->column(6, new MetricUsers());
                $row->column(6, new MetricRechargePayRate());
            })
            ->body(function (Row $row) {
                $row->column(6, new MetricRecharge());
                $row->column(6, new MetricWithdraw());
            })
            ->body("<div class='fs-25 text-bold padding-bottom'>统计数据</div>")
            ->body(function (Row $row) {
                $row->column(6, new MetricSumWallet());
                $row->column(6, new MetricRechargeChannel());
            });

        if (\Admin::user()->isAdministrator()) {
            $content->body(function (Row $row) {
                $row->column(12, new MetricChannel());
            });
        }

        return $content;
    }
}
