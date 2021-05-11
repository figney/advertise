<?php

namespace App\Admin\Forms;

use App\Enums\TransferVoucherCheckType;
use App\Enums\WalletLogType;
use App\Models\UserTransferVoucher;
use App\Models\UserTransferVoucherCheckLog;
use App\Models\Wallet;
use App\Models\WalletLog;
use App\Services\RechargeService;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;

class TransferVoucherPassForm extends Form implements LazyRenderable
{
    use LazyWidget;


    public function handle(array $input)
    {

        try {

            $check_money = (float)data_get($input, 'check_money');
            $id = $this->payload['id'];
            $userTransferVoucher = UserTransferVoucher::whereCheckType(TransferVoucherCheckType::UnderReview)->whereStatus(false)->findOrFail($id);

            abort_if($check_money !== (float)$userTransferVoucher->amount, 400, "查账金额与订单金额不符");

            $action_type = WalletLogType::DepositTransferVoucherRecharge;
            $order = $userTransferVoucher->userRechargeOrder;

            $order->remark_slug = "审核通过";
            $order->back_time = now();


            RechargeService::make()->rechargeOrderSuccess($order, $action_type, function (Wallet $wallet, WalletLog $walletLog) use ($userTransferVoucher) {

                $userTransferVoucher->check_type = TransferVoucherCheckType::Pass;
                $userTransferVoucher->check_slug = "审核通过";
                $userTransferVoucher->status = 1;
                $userTransferVoucher->check_time = now();
                $userTransferVoucher->wallet_log_id = $walletLog->id;
                $userTransferVoucher->save();

            });
            //写入审核记录
            UserTransferVoucherCheckLog::query()->create([
                'user_transfer_voucher_id' => $userTransferVoucher->id,
                'admin_user_id' => \Admin::user()->id,
                'check_type' => $userTransferVoucher->check_type,
                'check_slug' => $userTransferVoucher->check_slug,
            ]);
            //发送消息通知
            //$user->notify(new TransferVoucherPassNotification($userTransferVoucher));

            return $this->response()->success("操作成功")->refresh();


        } catch (\Exception $exception) {
            return $this->response()->error($exception->getMessage())->alert();
        }
    }

    public function form()
    {
        $userTransferVoucher = UserTransferVoucher::query()->find($this->payload['id']);

        //$this->display('转账金额')->default(number_format($userTransferVoucher->amount));
        $this->display('转账人名称')->default($userTransferVoucher->user_name);
        $this->display('转账卡号')->default($userTransferVoucher->card_number);
        $this->display('银行名称')->default($userTransferVoucher->bank_name);

        $this->number('check_money', '输入查账金额')->required();

    }

}
