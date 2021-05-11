<?php


namespace App\Admin\Actions\Grid;


use App\Enums\WalletType;
use App\Enums\WithdrawOrderStatusType;
use App\Models\UserWithdrawOrder;
use App\Services\WithdrawService;
use Dcat\Admin\Grid\RowAction;
use Illuminate\Http\Request;

class WithdrawOrderRefundAction extends RowAction
{
    protected $title = "<button class='btn btn-warning btn-sm margin-right-xs'>钱包退款</button>";

    public function handle(Request $request)
    {

        try {


            $userWithdrawOrder = UserWithdrawOrder::query()->find($this->getKey());
            abort_if(!in_array($userWithdrawOrder->order_status, [WithdrawOrderStatusType::CheckError, WithdrawOrderStatusType::PayError]), 400, '当前订单状态无法操作');
            //退款操作
            WithdrawService::make()->refundWithdrawOrder($userWithdrawOrder);
            return $this->response()->success("操作成功")->refresh();
        } catch (\Exception $exception) {
            return $this->response()->error($exception->getMessage())->alert();
        }
    }


    public function confirm()
    {

        /**@var UserWithdrawOrder $item */
        $item = $this->row;

        $amount = (float)$item->amount;

        $desc = "<div class='fs-25 text-danger'>" . "退款金额：" . $amount . '  ' . WalletType::fromValue($item->wallet_type)->description . "</div>";

        return ['你确定要退款当前提现么？', $desc];
    }

}
