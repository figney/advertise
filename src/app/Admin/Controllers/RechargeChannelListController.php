<?php

namespace App\Admin\Controllers;

use App\Enums\RechargeChannelType;
use App\Models\RechargeChannelList;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class RechargeChannelListController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new RechargeChannelList(), function (Grid $grid) {

            $grid->model()->with(['rechargeChannel'])->where('recharge_channel_id', request('recharge_channel_id'))->orderBy('order', 'desc');

            $grid->column('id')->sortable();
            $grid->column('bank_code');
            $grid->column('bank_cover')->image('', 50, 50);
            $grid->column('bank_name');
            $grid->column('card_bank_name');
            $grid->column('card_number');
            $grid->column('card_user_name');
            $grid->column('max_money');
            $grid->column('min_money');
            $grid->column('name');
            $grid->column('order')->editable();
            $grid->column('rechargeChannel.name');
            $grid->column('status')->switch();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');

            });

            $grid->disableCreateButton();
            $grid->disableDeleteButton();
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
        return Show::make($id, new RechargeChannelList(), function (Show $show) {
            $show->field('id');
            $show->field('bank_code');
            $show->field('bank_cover');
            $show->field('bank_name');
            $show->field('card_bank_name');
            $show->field('card_number');
            $show->field('card_user_name');
            $show->field('max_money');
            $show->field('min_money');
            $show->field('name');
            $show->field('order');
            $show->field('recharge_channel_id');
            $show->field('son_bank_list');
            $show->field('status');
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
        return Form::make(new RechargeChannelList(), function (Form $form) {
            $form->text('name')->required();
            if ($form->model()->type == RechargeChannelType::TransferAccounts) {
                $form->text('card_user_name', '收款人姓名')->required();
                $form->text('card_number', '收款人账户')->required();
                $form->text('card_bank_name', '开户行全称')->required();
            }

            if ($form->model()->type == RechargeChannelType::OnLine) {
                $form->image('bank_cover', '银行图标')->autoUpload()->required()->width(2);
                $form->text('bank_name', '银行名称')->required();
                $form->text('bank_code', '银行代码')->required();
            }

            $form->number('order', '排序')->default(1)->required();
            $form->number('min_money', '最低金额')->default(1)->required()->help('防止用户输入省略金额');
            $form->number('max_money', '最大金额')->help('0为不限制');

            $form->switch('status', '启用');

            $form->table('son_bank_list', '子银行选项', function (Form\NestedForm $table) {
                $table->text('code', '银行代码');
                $table->text('name', '银行名称');
                $table->switch('status', '状态');

            });
        });
    }
}
