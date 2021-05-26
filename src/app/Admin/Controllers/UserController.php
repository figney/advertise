<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\GetUserJwtToken;
use App\Admin\Actions\Grid\SetUserPassword;
use App\Admin\Actions\Grid\UserWallet;
use App\Admin\Actions\Grid\UserWalletLogAction;


use App\Models\User;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Auth\Permission;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;


class UserController extends AdminController
{

    use Base;

    protected $title = "用户";

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new User(), function (Grid $grid) {

            $grid->model()->with(['invite', 'ips', 'wallet', 'channel', 'walletCount', 'withdrawOrdersChecking', 'vips'])->orderBy('id', 'desc');

            if (!$this->isAdministrator()) {
                $grid->model()->byChannel();
            }

            //$grid->column('activity', '激活')->bool()->width(60);

            $grid->column('user')->display(fn() => $this)->userInfo();

            $grid->column('status')->switch();

            $grid->column('invite_id')->display(function ($v) {
                $html = "";
                $html .= "<div><a href='" . admin_route('user_list.index', ['invite_id' => $v]) . "'>邀请人ID：" . $v . "</a></div>";
                $v > 0 ? $html .= "<div class='margin-top-xs'><a href='" . admin_route('user_list.index', ['id' => $v]) . "'>查看邀请人</a></div>" : null;
                return $html;
            });
            $grid->column('created_at', '注册时间')->display(function () {
                $html = "";
                $html .= "<div class='margin-top-xs'>语言包：" . $this->local . "</div>";
                $html .= "<div class='margin-top-xs'>语言：" . $this->lang . "</div>";
                $html .= "<div class='margin-top-xs'>注册时间：" . $this->created_at . "</div>";
                return $html;
            });


            $grid->column('source')->display(function () {
                $html = "";
                $html .= "<div class='margin-top-xs'>渠道：" . $this->channel?->name . "</div>";
                $html .= "<div class='margin-top-xs'>推广ID：" . $this->link_id . "</div>";
                $html .= "<div class='margin-top-xs'>来源：" . $this->source . "</div>";
                $html .= "<div class='margin-top-xs'>客服ID：" . $this->channel_service_id . "</div>";
                return $html;
            });

            $grid->column('user_p_info', '注册信息')->display(function () {
                $html = "";
                $html .= "<div class='margin-top-xs'>国家码：" . $this->country_code . "</div>";
                $html .= "<div class='margin-top-xs'>手机号：" . $this->national_number . "</div>";
                $html .= "<div class='margin-top-xs'>注册IP：" . $this->ip . "-<span class='text-danger text-bold'>" . $this->ips->count() . "</span></div>";
                $html .= "<div class='margin-top-xs'>设备标识：" . $this->imei . "</div>";
                if (\Admin::user()->isAdministrator()) {
                    $html .= "<a href='" . admin_route('devices.index', ['imei' => $this->imei]) . "' class='margin-top-xs'>设备信息</a>";
                    $html .= "<a href='" . admin_route('device_logs.index', ['imei' => $this->imei]) . "' class='margin-top-xs margin-left-xs'>设备轨迹</a>";
                    $html .= "<a href='" . admin_route('device_logs.index', ['user_id' => $this->id]) . "' class='margin-top-xs margin-left-xs'>用户轨迹</a>";
                    $html .= "<a href='" . admin_route('devices.index', ['user_id' => $this->id]) . "' class='margin-top-xs margin-left-xs'>用户设备</a>";
                }
                return $html;
            });


            $grid->column('wallet_info', '钱包信息')->display(function () {
                $html = "";
                $html .= "<div>余额：" . (float)$this->wallet->balance . "</div>";
                $html .= "<div class='margin-top-xs'>USDT余额：" . (float)$this->wallet->usdt_balance . "</div>";
                $html .= "<div class='margin-top-xs'>赠送金余额：" . (float)$this->wallet->give_balance . "</div>";
                return $html;
            })->sortable('wallet.balance');


            $grid->column('invite.total_all', '下级数量')->sortable();
            $grid->column('invite.level', '层级')->sortable();

            if ($this->isAdministrator()) {
                $grid->column('tester', '测试员')->switch();
            }


            $grid->filter(function (Grid\Filter $filter) {

                $filter->equal('status')->radio([1 => '正常', 0 => '禁用'])->width(12);
                $filter->equal('tester', '测试员')->radio([1 => '测试员', 0 => '非测试员'])->width(12);
                $filter->equal('id')->width(2);
                $filter->equal('invite_id')->width(2);
                $filter->like('national_number')->width(2);

                $filter->gt('recharge_count', '充值次数大于')->width(2);
                $filter->where('is_share', function ($q) {
                    if ($this->input == 1) {
                        $q->where('invite_id', '>', 0);
                    }
                }, '邀请状态')->radio([1 => '有邀请', 0 => '直接注册'])->width(2);

                $filter->date('created_at');
            });

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->append(new UserWalletLogAction($actions->row->id));
                if (\Admin::user()->can("user-wallet-set")) $actions->append(new UserWallet($actions->row->id));
                if (\Admin::user()->isAdministrator()) $actions->append(new GetUserJwtToken());
                if (\Admin::user()->can("reset-user-password")) $actions->append(new SetUserPassword());
            });


            $grid->disableCreateButton();
            $grid->disableDeleteButton();
            $grid->disableBatchDelete();
            $grid->disableRowSelector();

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
        return Show::make($id, new User(), function (Show $show) {

        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new User(), function (Form $form) {

            $form->switch('status');
            $form->switch('tester');

            if (\Admin::user()->isAdministrator()) {


                $form->text('password')->customFormat(function () {
                    return '';
                });

                $form->password('password_confirmation', trans('admin.password_confirmation'))->same('password');

                $form->ignore(['password_confirmation']);

                $form->saving(function (Form $form) {
                    if ($form->password && $form->model()->get('password') != $form->password) {
                        $form->password = bcrypt($form->password);
                    }

                    if (!$form->password) {
                        $form->deleteInput('password');
                    }
                });
            }

        });
    }
}
