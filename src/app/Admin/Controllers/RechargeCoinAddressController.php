<?php

namespace App\Admin\Controllers;

use App\Models\CoinAddress;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class RechargeCoinAddressController extends AdminController
{

    protected $title = "USDT地址";

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new CoinAddress(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('address');
            $grid->column('allocation_time');
            $grid->column('amount')->sortable()->money(true);
            $grid->column('currency');
            $grid->column('recharge_channel_id');
            $grid->column('user_id');

            $grid->column('created_at');;

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');

            });

            $count = CoinAddress::query()->disableCache()->where('user_id', 0)->count();
            $s_count = CoinAddress::query()->disableCache()->where('user_id', '>', 0)->count();

            $grid->header("<div class='alert alert-primary mt-1'>未使用：$count , 已使用：$s_count</div>");

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
        return Show::make($id, new CoinAddress(), function (Show $show) {
            $show->field('id');
            $show->field('address');
            $show->field('allocation_time');
            $show->field('amount');
            $show->field('currency');
            $show->field('recharge_channel_id');
            $show->field('user_id');
            $show->field('uuid');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new RechargeCoinAddress(), function (Form $form) {
            $form->display('id');
            $form->text('address');
            $form->text('allocation_time');
            $form->text('amount');
            $form->text('currency');
            $form->text('recharge_channel_id');
            $form->text('user_id');
            $form->text('uuid');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
