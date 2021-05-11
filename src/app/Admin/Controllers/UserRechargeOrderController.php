<?php

namespace App\Admin\Controllers;

use App\Enums\OrderStatusType;
use App\Enums\RechargeChannelType;
use App\Enums\WalletType;
use App\Enums\WithdrawChannelType;
use App\Enums\WithdrawOrderStatusType;
use App\Models\UserRechargeOrder;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UserRechargeOrderController extends AdminController
{
    use Base;

    protected $title = "充值";

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new UserRechargeOrder(), function (Grid $grid) {


            $grid->model()->with(['rechargeChannel', 'user', 'user.wallet', 'user.walletCount', 'user.withdrawOrdersChecking','user.vips'])->orderBy('id', 'desc');

            if (!$this->isAdministrator()) {
                $grid->model()->byChannel();
            }

            $grid->column('id')->sortable()->minWidth(100);
            $grid->column('order_sn')->minWidth(180);
            $grid->column('user')->userInfo()->minWidth(350);
            $grid->column('channel_id')->display(function () {

                /**@var UserRechargeOrder $this */

                $html = "";
                $html .= "<div>渠道ID：{$this->channel_id}</div>";
                $html .= "<div class='margin-top-xs'>推广ID：{$this->link_id}</div>";
                $html .= "<div class='margin-top-xs'>来源：{$this->user->source}</div>";
                return $html;
            })->minWidth(100);
            $grid->column('wallet_type')->using(WalletType::asSelectArray())->filter()->minWidth(100);
            $grid->column('amount')->display(fn($v) => ShowMoney($v, $this->wallet_type == WalletType::usdt))->minWidth(150);
            //$grid->column('actual_amount');
            $grid->column('recharge_type')->using(RechargeChannelType::asSelectArray())->minWidth(100);

            $grid->column('platform_sn');
            $grid->column('rechargeChannel.name', '支付渠道')->filter()->minWidth(150);
            $grid->column('rechargeChannelItem.name', '支付渠道项')->filter()->minWidth(150);
            $grid->column('son_code', '子银行')->filter()->minWidth(100);
            $grid->column('remark_slug', '备注')->display(fn() => $this->remarkContent());
            $grid->column('is_pay')->bool();
            $grid->column('pay_time');
            $grid->column('order_status')->using(OrderStatusType::asSelectArray())->label([
                OrderStatusType::Paying => "info",
                OrderStatusType::PaySuccess => "success",
                OrderStatusType::PayError => "danger",
                OrderStatusType::Close => "dark",
            ])->minWidth(100);
            $grid->column('created_at')->minWidth(180);

            $grid->fixColumns(0, 0);

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id', '用户ID')->width(1);
                $filter->equal('channel_id', '渠道ID')->width(1);
                $filter->equal('order_sn')->width(2);
                $filter->date('created_at')->width(2);
            });

            $grid->selector(function (Grid\Tools\Selector $selector) {
                $selector->select('order_status', '订单状态', OrderStatusType::asSelectArray());
                $selector->select('wallet_type', '钱包类型', WalletType::asSelectArray());
            });

            $grid->disableDeleteButton();
            if (!\Admin::user()->isAdministrator()) {
                $grid->disableEditButton();
            }
            $grid->disableCreateButton();
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
        return Show::make($id, new UserRechargeOrder(), function (Show $show) {

        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new UserRechargeOrder(), function (Form $form) {
            $form->select('order_status')->options(OrderStatusType::asSelectArray());
        });
    }
}
