<?php


namespace App\Admin\Controllers;


use App\Admin\Metrics\MetricDeviceLog;
use App\Models\DeviceLog;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;

class DeviceLogController extends AdminController
{

    use Base;

    protected $title = "行为轨迹";

    protected function grid()
    {
        return Grid::make(new DeviceLog(), function (Grid $grid) {

            if (!$this->isAdministrator()) {
                $grid->model()->byChannel(null, false);
            }


            $grid->model()
                //->with(['user', 'user.walletCount', 'user.withdrawOrdersChecking'])
                ->orderBy('id', 'desc')
                ->simplePaginate();

            $grid->column('id', 'ID')->sortable();
            $grid->column('created_at', '创建时间')->sortable();
            $grid->column('imei', 'IMEI')->filter();
            $grid->column('type')->filter();
            $grid->column('user_id', '用户')->filter();
            $grid->column('lang', '客户端语言')->filter();
            $grid->column('local', '语言包')->filter();
            $grid->column('event_name', '事件名称')->filter();
            $grid->column('untitled_page', '页面名称')->filter();
            $grid->column('untitled_url', '页面地址')->filter();
            $grid->column('version', '版本')->filter();
            $grid->column('ip', 'IP')->filter();


            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('imei', 'IMEI')->width(2);
                $filter->like('event_name', '事件名称')->width(2);
                $filter->where('user_id', function ($q) {
                    $q->where('user_id', (int)$this->input);
                }, '用户ID')->width(2);

                $filter->date('created_at')->width(2);
            });


            $grid->tools(function (Grid\Tools $tools) {
                $tools->append("<a class='btn btn-primary' href='" . admin_url('device_log_statistics') . "' >数据统计</a>");
            });


            $grid->disableCreateButton();
            $grid->disableDeleteButton();
            $grid->disableActions();

        });
    }


    public function statistics(Content $content)
    {

        $content->body(new MetricDeviceLog());

        return $content;
    }

}
