<?php

namespace App\Admin\Controllers;

use App\Models\Language;
use App\Models\WithdrawChannelList;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class WithdrawChannelListController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new WithdrawChannelList(), function (Grid $grid) {

            $grid->model()->with(['withdrawChannel'])->where('withdraw_channel_id', request('withdraw_channel_id'))->orderBy('order', 'desc');

            $grid->column('name');
            $grid->column('bank_cover')->image('', 50, 50);
            $grid->column('bank_code');
            $grid->column('bank_name')->editable();
            $grid->column('min_money')->editable();
            $grid->column('max_money', '最高提现金额')->editable();
            $grid->column('status')->switch();
            $grid->column('order')->sortable()->editable();


            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');

            });

            $grid->disableDeleteButton();
            $grid->disableCreateButton();

        });
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new WithdrawChannelList(), function (Form $form) {
            $language = Language::query()->get();
            $form->hidden('withdraw_channel_id', request('withdraw_channel_id'));
            $form->text('name');
            $form->image('bank_cover')->autoUpload()->uniqueName()->width(2);
            $form->text('bank_code');
            $form->text('bank_name');
            $form->number('min_money');
            $form->number('max_money', '最大金额');
            $form->switch('status');
            $form->number('order');

            $form->embeds('remark', '提现说明', function (Form\EmbeddedForm $form) use ($language) {
                foreach ($language as $lang) {
                    $lang->show ? $form->textarea($lang->slug, $lang->name) : $form->hidden($lang->slug, $lang->name);
                }
            });

            $form->table('input_config', '表单参数', function (Form\NestedForm $table) {
                $table->text('name', '参数名称');
                $table->text('slug', '语言标识');
                $table->text('desc', '参数说明');
            });

        });
    }
}
