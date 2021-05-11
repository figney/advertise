<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\CheckUserWithdrawPayStatus;
use App\Admin\Actions\Grid\UserWithdrawCheckDataUpdate;
use App\Admin\Actions\Grid\WithdrawOrderPassAction;
use App\Admin\Actions\Grid\WithdrawOrderRefundAction;
use App\Admin\Actions\Grid\WithdrawOrderRejectAction;
use App\Enums\PlatformType;
use App\Enums\WalletType;
use App\Enums\WithdrawChannelType;
use App\Enums\WithdrawOrderStatusType;
use App\Models\UserWithdrawOrder;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UserWithdrawOrderController extends AdminController
{

    use Base;

    protected $title = "提现记录";

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new UserWithdrawOrder(), function (Grid $grid) {


            $grid->model()->with(['user', 'user.withdrawOrdersChecking', 'user.vips', 'withdrawChannel', 'withdrawChannelItem', 'checkData'])->orderBy('id', 'desc');
            if (!$this->isAdministrator()) {
                $grid->model()->byChannel();
            }

            $grid->column('order_sn')->display(function () {
                $html = "<div>系统订单ID：" . $this->id . "</div>";
                $html .= "<div class='margin-top-xs'>系统订单号：</div><div class='margin-top-xs'>" . $this->order_sn . "</div>";
                $html .= "<div class='margin-top-xs'>代付订单号：</div><div class='margin-top-xs'>" . $this->platform_sn . "</div>";

                return $html;
            })->minWidth(200);
            $grid->column('user')->minWidth(350)->userInfo();

            $grid->column('amount_info', '提现金额信息')->minWidth(150)->display(function () {
                $html = "<div class='text-bold text-dark'>钱包类型：" . WalletType::fromValue($this->wallet_type)->description . "</div>";
                $html .= "<div class='margin-tb-xs '>提现金额：" . MoneyShow($this->actual_amount) . "</div>";
                $html .= "<div class='margin-tb-xs '>扣除金额：" . MoneyShow($this->amount) . "</div>";
                $html .= "<div class='margin-tb-xs'>手续费：" . (float)$this->rate_amount . "-" . (float)$this->rate . "%</div>";

                return $html;
            });

            $grid->column('withdraw_type')->display(function () {
                /** @var UserWithdrawOrder $this */
                $html = "<div class='fs-12'><div>提现类型：" . WithdrawChannelType::fromValue($this->withdraw_type)->description . "</div>";
                $html .= "<div class='margin-tb-xs'>提现通道：" . PlatformType::fromValue($this->withdrawChannel->slug)->description . "</div>";
                $html .= "<div class='margin-tb-xs'>渠道名称：" . $this->withdrawChannel->name . "</div>";
                $html .= "<div class='margin-tb-xs'>渠道选项：" . $this->withdrawChannelItem?->name . "</div></div>";
                return $html;

            })->minWidth(180);


            $grid->column('order_status')->using(WithdrawOrderStatusType::asSelectArray())->badge([
                WithdrawOrderStatusType::Checking => 'warning',
                WithdrawOrderStatusType::CheckSuccess => 'success',
                WithdrawOrderStatusType::CheckError => 'danger',
                WithdrawOrderStatusType::CheckErrorAndRefund => 'dark',
                WithdrawOrderStatusType::Paying => 'warning',
                WithdrawOrderStatusType::PayError => 'danger',
                WithdrawOrderStatusType::Close => 'info',
            ])->minWidth(100);
            $grid->column('auto_check')->bool()->minWidth(80);
            $grid->column('actual_amount')->display(fn($v) => ShowMoney($v, $this->wallet_type == WalletType::usdt));
            $grid->column('input_data')->display(function ($array) {
                $html = "";

                foreach ($array as $key => $val) {
                    $html .= "<div class='text-dark font-bold'>";
                    $html .= "<span>$key</span>：";
                    $html .= "<span>$val</span>";
                    $html .= "</div>";
                    $html .= "<div>";
                    $html .= "<span>申请：" . data_get($this->checkData, $key . ".count", "-") . "</span>";
                    $html .= "<span class='ml-1 '>成功：" . data_get($this->checkData, $key . ".success_count", "-") . "</span>";
                    $html .= "<span class='ml-1 '>用户：" . data_get($this->checkData, $key . ".user_count", "-") . "</span>";
                    $html .= "</div>";
                }

                $html .= "";

                return $html;

            })->minWidth(350);

            $grid->column('ip')->display(function () {
                /** @var UserWithdrawOrder $this */
                $html = "<div class='fs-12'><div>语言包：" . $this->local . "</div>";
                $html .= "<div class='margin-tb-xs'>用户语言：" . $this->lang . "</div>";
                $html .= "<div class='margin-tb-xs'>IP：" . $this->ip . "</div></div>";
                $html .= "<div class='margin-tb-xs'>IMEI：" . $this->imei . "</div></div>";
                return $html;
            })->minWidth(180);

            $grid->column('pay_time')->display(function () {
                /** @var UserWithdrawOrder $this */
                $html = "<div>创建时间：" . $this->created_at . "</div>";
                $html .= "<div class='margin-tb-xs'>处理时间：" . $this->back_time . "</div>";
                $html .= "<div  class='margin-tb-xs'>付款时间：" . $this->pay_time . "</div>";
                return $html;
            })->minWidth(300);
            $grid->column('remark')->display(fn() => $this->remarkContent())->minWidth(350);

            $grid->filter(function (Grid\Filter $filter) {
                $filter->panel();
                $filter->equal('user_id', '用户ID')->width(2);

                $filter->between('created_at', '创建时间')->date()->width(3);

            });
            $grid->quickSearch(['ip', 'imei', 'order_sn', 'user_id'])->auto(false);

            $grid->actions(function (Grid\Displayers\Actions $actions) {

                /**@var UserWithdrawOrder $item */
                $item = $actions->row;

                if ($this->isChannel() || $this->isAdministrator()) {
                    if ($item->order_status === WithdrawOrderStatusType::Checking) $actions->append(WithdrawOrderRejectAction::make());
                    if (in_array($item->order_status, [WithdrawOrderStatusType::CheckError, WithdrawOrderStatusType::PayError]) && \Admin::user()->can('user-refund')) $actions->append(WithdrawOrderRefundAction::make());
                    if ($item->order_status === WithdrawOrderStatusType::Checking && \Admin::user()->can('user-refund')) $actions->append(WithdrawOrderPassAction::make());
                    if ($item->order_status === WithdrawOrderStatusType::Paying) {
                        $actions->append(CheckUserWithdrawPayStatus::make());
                        $actions->append(WithdrawOrderRejectAction::make());
                    }
                }

                $actions->append(UserWithdrawCheckDataUpdate::make());


            })->setActionClass('123465');

            $grid->fixColumns(0, -1);
            $grid->selector(function (Grid\Tools\Selector $selector) {
                $selector->select('order_status', '订单状态', WithdrawOrderStatusType::asSelectArray());
                $selector->select('wallet_type', '钱包类型', WalletType::asSelectArray());
                $selector->select('withdraw_type', '提现类型', WithdrawChannelType::asSelectArray());
            });

            $grid->disableEditButton();
            $grid->disableCreateButton();
            $grid->disableDeleteButton();

            $grid->setActionClass(\Dcat\Admin\Grid\Displayers\Actions::class);

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
        return Show::make($id, new UserWithdrawOrder(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('user_level');
            $show->field('channel_id');
            $show->field('wallet_type');
            $show->field('amount');
            $show->field('actual_amount');
            $show->field('rate');
            $show->field('rate_amount');
            $show->field('withdraw_type');
            $show->field('order_sn');
            $show->field('platform_sn');
            $show->field('withdraw_channel_id');
            $show->field('withdraw_channel_item_id');
            $show->field('remark');
            $show->field('remark_slug');
            $show->field('is_pay');
            $show->field('pay_time');
            $show->field('wallet_log_id');
            $show->field('back_wallet_log_id');
            $show->field('order_status');
            $show->field('back_time');
            $show->field('local');
            $show->field('lang');
            $show->field('auto_check');
            $show->field('input_data');
            $show->field('ip');
            $show->field('imei');
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
        return Form::make(new UserWithdrawOrder(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('user_level');
            $form->text('channel_id');
            $form->text('wallet_type');
            $form->text('amount');
            $form->text('actual_amount');
            $form->text('rate');
            $form->text('rate_amount');
            $form->text('withdraw_type');
            $form->text('order_sn');
            $form->text('platform_sn');
            $form->text('withdraw_channel_id');
            $form->text('withdraw_channel_item_id');
            $form->text('remark');
            $form->text('remark_slug');
            $form->text('is_pay');
            $form->text('pay_time');
            $form->text('wallet_log_id');
            $form->text('back_wallet_log_id');
            $form->text('order_status');
            $form->text('back_time');
            $form->text('local');
            $form->text('lang');
            $form->text('auto_check');
            $form->text('input_data');
            $form->text('ip');
            $form->text('imei');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
