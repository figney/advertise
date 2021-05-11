<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\TransferVoucherPassAction;
use App\Admin\Actions\Grid\TransferVoucherRejectAction;
use App\Admin\Actions\Grid\UserWalletLogAction;
use App\Enums\TransferVoucherCheckType;
use App\Models\UserTransferVoucher;
use Carbon\Carbon;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;

class UserTransferVoucherController extends AdminController
{

    use Base;

    protected $title = "转账";

    /**
     * Make a grid builder.
     * TransferVoucherPassAction
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new UserTransferVoucher(), function (Grid $grid) {

            $grid->model()->with(['channelItem', 'user', 'user.wallet', 'user.walletCount', 'user.withdrawOrdersChecking','user.vips'])->orderBy('id', 'desc');

            $grid->column('check_type')
                ->using(TransferVoucherCheckType::asSelectArray())
                ->label([
                    TransferVoucherCheckType::Reject => 'danger',
                    TransferVoucherCheckType::UnderReview => 'primary',
                    TransferVoucherCheckType::Pass => 'success',
                ])->minWidth(100);

            if ($this->isAdministrator() || $this->isChannel()) {
                $grid->column('user')->minWidth(350)->userInfo();
            } else {
                $grid->column('user_id');
            }


            $grid->column('ut_info', '转账信息')->display(function () {
                $html = "<div>名称：{$this->user_name}</div>";
                $html .= "<div class='mt-sm-1'>卡号：{$this->card_number}</div>";
                $html .= "<div class='mt-sm-1'>银行：{$this->bank_name}</div>";

                return $html;
            })->minWidth(150);

            $grid->column('amount')->sortable()->money()->minWidth(150);

            $grid->column('time')->minWidth(150);
            //$grid->column('status');

            //$grid->column('check_slug');
            //$grid->column('check_time');
            //$grid->column('wallet_log_id');

            $grid->column('image')->image('', 50, 100)->minWidth(100);

            $grid->column('item_info', '收款卡信息')->display(function () {
                $html = "<div>银行：{$this->channelItem->card_user_name}</div>";
                $html .= "<div class='mt-sm-1'>用户：{$this->channelItem->card_number}</div>";
                $html .= "<div class='mt-sm-1'>卡号：{$this->channelItem->card_bank_name}</div>";


                return $html;


            })->minWidth(250);

            $grid->column('created_at')->minWidth(180);
            //$grid->column('updated_at')->sortable();

            $grid->column('check_slug', '审核理由');

            $grid->selector(function (Grid\Tools\Selector $selector) {
                $selector->select('check_type', '审核状态', TransferVoucherCheckType::asSelectArray());
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id')->width(2);

                $filter->date('created_at')->width(2);

            });

            $grid->actions(function (Grid\Displayers\Actions $actions) {


                $status = $actions->row->status;
                $check_type = $actions->row->check_type;

                if ($check_type == TransferVoucherCheckType::UnderReview) $actions->append(TransferVoucherRejectAction::make($actions->row)->addHtmlClass('mr-1 text-danger'));
                if ($check_type == TransferVoucherCheckType::UnderReview) $actions->append(TransferVoucherPassAction::make()->addHtmlClass('mr-1 text-success'));
                if (\Admin::user()->isAdministrator()) $actions->append(new UserWalletLogAction($actions->row->user_id));
            });
            //$grid->fixColumns(0, -1);

            $grid->setActionClass(\Dcat\Admin\Grid\Displayers\Actions::class);


            $grid->disableEditButton();


            $grid->disableDeleteButton();
            $grid->disableCreateButton();

            if (request('created_at')){

                $created_at = request('created_at');

                $amount_count = UserTransferVoucher::query()
                    ->whereDate('created_at',Carbon::make($created_at))
                    ->where('check_type',TransferVoucherCheckType::Pass)
                    ->noTester()
                    ->sum('amount');



                $money = ShowMoney($amount_count);

                $content=<<<HTML
<div class="alert alert-info">
当前日前入账统计：
<div class="">$money</div>
</div>

HTML;

                $grid->header($content);
            }

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
        return Show::make($id, new UserTransferVoucher(), function (Show $show) {

        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new UserTransferVoucher(), function (Form $form) {

        });
    }
}
