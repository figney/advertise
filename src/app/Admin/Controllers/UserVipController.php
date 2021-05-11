<?php

namespace App\Admin\Controllers;

use App\Models\UserVip;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UserVipController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(UserVip::query()->with(['vip', 'user', 'user.wallet', 'user.walletCount', 'user.withdrawOrdersChecking', 'user.vips']), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->column('user')->userInfo();


            $grid->column('fy_money')->display(fn($v) => ShowMoneyLine($v));
            $grid->column('get_commission_type');
            $grid->column('level');

            $grid->column('channel_id');
            $grid->column('link_id');
            $grid->column('vip.name', 'VIP套餐');
            $grid->column('vip_money')->display(fn($v) => ShowMoneyLine($v))->sortable();
            $grid->column('task_num', '任务次数')->sortable();
            $grid->column('buy_number_count', '叠加次数')->sortable();
            $grid->column('buy_money_count', '消费金额')->sortable()->display(fn($v) => ShowMoneyLine($v));
            $grid->column('created_at');

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('channel_id')->width(2);
                $filter->equal('link_id')->width(2);
                $filter->equal('user_id')->width(2);
                $filter->equal('level')->width(2);
                $filter->date('created_at')->width(2);
                $filter->ngt('buy_number_count', '叠加次数 >=')->width(2);
                $filter->ngt('buy_money_count', '消费金额 >=')->width(2);
            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new UserVip(), function (Show $show) {

        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new UserVip(), function (Form $form) {

        });
    }
}
