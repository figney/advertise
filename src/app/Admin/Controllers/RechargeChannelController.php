<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\SyncBankList;
use App\Enums\PlatformType;
use App\Enums\RechargeChannelType;
use App\Enums\WithdrawChannelType;
use App\Models\Language;
use App\Models\RechargeChannel;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class RechargeChannelController extends AdminController
{

    protected $title = "充值渠道";

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new RechargeChannel(), function (Grid $grid) {
            $grid->column('cover')->image('', 50, 50);
            $grid->column('type')->using(RechargeChannelType::asSelectArray())->filter();
            $grid->column('name');
            $grid->column('slug', '接入平台')->using(PlatformType::asSelectArray());
            $grid->column('title', '显示名称')->localData();
            $grid->column('min_money', '最低充值金额')->display(fn($v) => ShowMoney($v, $this->type == RechargeChannelType::USDT_TRC20));
            $grid->column('max_money', '最高充值金额')->display(fn($v) => ShowMoney($v, $this->type == RechargeChannelType::USDT_TRC20));
            $grid->column('order', '未充值用户排序')->sortable()->editable();
            $grid->column('order_already', '已充值用户排序')->sortable()->editable();
            $grid->column('weight', '权重')->editable();
            $grid->column('status', '未充值用户显示')->switch();
            $grid->column('status_already', '已充值用户显示')->switch();
            $grid->column('select_bank', '先选择银行')->switch();


            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id', '备注');
            });

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $item = $actions->row;
                if ($item->select_bank) {
                    $actions->append(new SyncBankList());
                    $actions->append("<a href='" . admin_url("recharge_channel_list?recharge_channel_id=" . $actions->getKey()) . "'>选项列表</a>");
                }
            });

            $grid->disableDeleteButton();

        });
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {


        $builder = RechargeChannel::with(['channelList']);

        return Form::make($builder, function (Form $form) {
            $language = Language::query()->get();

            $form->radio('type')->options(RechargeChannelType::asSelectArray())->required()->width(3);

            $form->text('name')->required()->width(3);
            $form->radio('slug', '渠道标识')->options(PlatformType::asSelectArray())->required();
            $form->number('min_money', '最低充值金额')->required();
            $form->number('max_money', '最高充值金额')->required()->help('0为不限制');
            $form->number('order', '未充值用户排序')->required();
            $form->number('order_already', '已充值用户排序')->required();
            $form->number('weight', '权重')->required();
            $form->switch('status', '未充值用户显示');
            $form->switch('status_already', '已充值用户显示');
            $form->switch('select_bank', '先选择银行');

            $form->embeds('title', '显示名称', function (Form\EmbeddedForm $form) use ($language) {
                foreach ($language as $lang) {
                    $lang->show ? $form->text($lang->slug, $lang->name)->required() : $form->hidden($lang->slug, $lang->name);
                }
            });

            $form->image('cover', '封面')->autoUpload()->uniqueName()->width(3);

            $form->embeds('remark', '充值说明', function (Form\EmbeddedForm $form) use ($language) {
                foreach ($language as $lang) {
                    $lang->show ? $form->textarea($lang->slug, $lang->name) : $form->hidden($lang->slug, $lang->name);
                }
            });

            $form->table('config', '渠道配置', function (Form\NestedForm $table) {
                $table->text('key', '配置标识');
                $table->text('value', '配置内容');
                $table->text('desc', '说明');
            });


            if ($form->isEditing()) {
                $form->hasMany('channel_list', '银行列表', function (Form\NestedForm $form) {

                    $form->text('name')->required();
                    if ($form->model()->type == RechargeChannelType::TransferAccounts) {
                        $form->text('card_user_name', '收款人姓名')->required();
                        $form->text('card_number', '收款人账户')->required();
                        $form->text('card_bank_name', '开户行全称')->required();
                    }

                    if ($form->model()->type == RechargeChannelType::OnLine) {
                        $form->image('bank_cover', '银行图标')->uniqueName()->autoUpload()->width(2);
                        $form->text('bank_name', '银行名称')->required();
                        $form->text('bank_code', '银行代码')->required();
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
