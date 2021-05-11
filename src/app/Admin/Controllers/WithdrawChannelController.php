<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\SyncBankList;
use App\Admin\Actions\Grid\SyncWithdrawBankList;
use App\Enums\PlatformType;
use App\Enums\WalletType;
use App\Enums\WithdrawChannelType;
use App\Models\Language;
use App\Models\WithdrawChannel;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class WithdrawChannelController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new WithdrawChannel(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('cover')->image('', 50, 50);
            $grid->column('name', '渠道名称');
            $grid->column('rate', '手续费')->display(fn($val) => (float)$val)->append('%');
            $grid->column('min_money')->display(fn($v) => ShowMoney($v, $this->type == WithdrawChannelType::USDT_TRC20));
            $grid->column('max_money', '最高提现金额')->help('0为不限制')->display(fn($v) => ShowMoney($v, $this->type == WithdrawChannelType::USDT_TRC20));

            $grid->column('type')->using(WithdrawChannelType::asSelectArray());
            $grid->column('slug', '接入平台')->using(PlatformType::asSelectArray());

            $grid->column('title')->localData();


            $grid->column('star_hour');
            $grid->column('end_hour');
            $grid->column('order')->editable();
            $grid->column('select_bank')->switch();
            $grid->column('status')->switch();
            $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');

            });

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $item = $actions->row;
                if ($item->select_bank) {
                    $actions->append(new SyncWithdrawBankList());
                }
                if ($actions->row->select_bank) $actions->append("<a href='" . admin_url("withdraw_channel_list?withdraw_channel_id=" . $actions->getKey()) . "'>选项列表</a>");
            });

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
        return Show::make($id, new WithdrawChannel(), function (Show $show) {
            $show->field('id');
            $show->field('name');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $builder = WithdrawChannel::with(['channelList']);

        return Form::make($builder, function (Form $form) {
            $language = Language::query()->get();
            $form->radio('type')->options(WithdrawChannelType::asSelectArray())->required();
            $form->text('name', '渠道名称')->required();
            $form->radio('slug', '渠道标识')->options(PlatformType::asSelectArray())->required();
            $form->rate('rate', '手续费')->required();
            $form->number('min_money')->required();
            $form->number('max_money', '最高提现金额')->required();
            $form->number('order')->required();
            $form->switch('select_bank');
            $form->switch('status');
            $form->embeds('title', '显示名称', function (Form\EmbeddedForm $form) use ($language) {
                foreach ($language as $lang) {
                    $lang->show ? $form->text($lang->slug, $lang->name)->required() : $form->hidden($lang->slug, $lang->name);
                }
            });
            $form->image('cover')->autoUpload()->uniqueName()->required()->width(2);
            $form->number('star_hour')->default(0);
            $form->number('end_hour')->default(24);
            $form->embeds('remark', '提现说明', function (Form\EmbeddedForm $form) use ($language) {
                foreach ($language as $lang) {
                    $lang->show ? $form->textarea($lang->slug, $lang->name)->required() : $form->hidden($lang->slug, $lang->name);
                }
            });

            $form->table('config', '渠道配置', function (Form\NestedForm $table) {
                $table->text('key', '配置标识');
                $table->text('value', '配置内容');
                $table->text('desc', '说明');
            });

            $form->table('input_config', '表单参数', function (Form\NestedForm $table) {
                $table->text('name', '参数名称');
                $table->text('slug', '语言标识');
                $table->text('desc', '参数说明');
            });

            if ($form->isEditing()) {
                $form->hasMany('channel_list', '选项列表', function (Form\NestedForm $form) {

                    $form->text('name', '选项名称')->required();
                    if ($form->model()->type == WithdrawChannelType::OnLine) {
                        $form->text('bank_name', '银行名称')->required();
                        $form->text('bank_code', '银行代码')->required();
                        $form->image('bank_cover', '银行图标')->autoUpload()->width(2);

                    }
                    $form->number('order', '排序')->default(1)->required();
                    $form->number('min_money', '最低金额')->default(1)->required()->help('防止用户输入省略金额');
                    $form->number('max_money', '最大金额')->help('0为不限制');
                    $form->switch('status', '启用');

                });
            }

        });
    }
}
