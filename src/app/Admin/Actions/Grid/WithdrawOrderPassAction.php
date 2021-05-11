<?php

namespace App\Admin\Actions\Grid;

use App\Enums\RechargeChannelType;
use App\Enums\TransferVoucherCheckType;
use App\Enums\WalletLogType;
use App\Enums\WalletType;
use App\Enums\WithdrawOrderStatusType;
use App\Models\Notifications\TransferVoucherPassNotification;
use App\Models\UserTransferVoucher;
use App\Models\UserTransferVoucherCheckLog;
use App\Models\UserWithdrawOrder;
use App\Models\Wallet;
use App\Models\WalletLog;
use App\Services\RechargeService;
use App\Services\WithdrawService;
use Dcat\Admin\Grid\RowAction;
use Illuminate\Http\Request;

class WithdrawOrderPassAction extends RowAction
{

    protected $title = "<button class='btn btn-success btn-sm margin-right-xs'>审核打款</button>";


    public function handle(Request $request)
    {
        try {
            $userWithdrawOrder = UserWithdrawOrder::query()->find($this->getKey());
            abort_if($userWithdrawOrder->order_status !== WithdrawOrderStatusType::Checking, 400, '当前订单状态无法操作');
            //打款操作
            WithdrawService::make()->paymentWithdrawOrder($userWithdrawOrder);

            return $this->response()->success('成功！')->refresh();
        } catch (\Exception $exception) {
            return $this->response()->error($exception->getMessage())->alert();
        }

    }

    public function confirm()
    {

        /**@var UserWithdrawOrder $item */
        $item = $this->row;

        $amount = (float)$item->actual_amount;

        $desc = "<div class='fs-25 text-danger'>" . "打款金额：" . $amount . '  ' . WalletType::fromValue($item->wallet_type)->description . "</div>";

        return ['你确定要打款当前提现么？', $desc];
    }

}
