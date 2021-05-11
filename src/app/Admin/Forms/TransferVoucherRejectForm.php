<?php

namespace App\Admin\Forms;

use App\Enums\TransferVoucherCheckType;
use App\Models\LanguageConfig;
use App\Models\Notifications\TransferVoucherRejectNotification;
use App\Models\UserTransferVoucher;
use App\Models\UserTransferVoucherCheckLog;
use App\Services\RechargeService;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;

class TransferVoucherRejectForm extends Form implements LazyRenderable
{
    use LazyWidget;


    public function handle(array $input)
    {
        try {
            \DB::beginTransaction();
            $userTransferVoucher = UserTransferVoucher::query()->with(['user', 'channelItem'])->find($this->payload['id']);
            $userTransferVoucher->check_type = TransferVoucherCheckType::Reject;
            $userTransferVoucher->check_slug = data_get($input, 'check_slug');
            $userTransferVoucher->status = 0;
            $userTransferVoucher->check_time = now();
            $userTransferVoucher->save();
            //发送消息通知
            $user = $userTransferVoucher->user;

            $order = $userTransferVoucher->userRechargeOrder;
            $order->remark_slug = data_get($input, 'check_slug');
            $order->back_time = now();

            RechargeService::make()->rechargeOrderError($order);


            $user->notify(new TransferVoucherRejectNotification($userTransferVoucher));

            \DB::commit();

            //写入审核记录
            UserTransferVoucherCheckLog::query()->create([
                'user_transfer_voucher_id' => $userTransferVoucher->id,
                'admin_user_id' => \Admin::user()->id,
                'check_type' => $userTransferVoucher->check_type,
                'check_slug' => $userTransferVoucher->check_slug,
            ]);

            return $this->response()->success("操作成功")->refresh();

        } catch (\Exception $exception) {
            return $this->response()->error($exception->getMessage());
        }


    }

    public function form()
    {
        $userTransferVoucher = UserTransferVoucher::query()->find($this->payload['id']);

        $this->display('转账金额')->default(number_format($userTransferVoucher->amount));
        $this->display('转账人名称')->default($userTransferVoucher->user_name);
        $this->display('转账卡号')->default($userTransferVoucher->card_number);
        $this->display('银行名称')->default($userTransferVoucher->bank_name);

        $this->select('check_slug', '驳回理由')->options(LanguageConfig::query()->where('group', '转账失败驳回理由')->pluck('name', 'slug'))->required();

    }

}
