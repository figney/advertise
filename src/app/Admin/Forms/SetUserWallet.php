<?php

namespace App\Admin\Forms;

use App\Enums\WalletLogSlug;
use App\Enums\WalletLogType;
use App\Enums\WalletType;
use App\Models\AdminUser;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletLog;
use App\Services\WalletService;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;

class SetUserWallet extends Form implements LazyRenderable
{
    use LazyWidget;

    /**
     * Handle the form request.
     *
     * @param array $input
     *
     * @return mixed
     */
    public function handle(array $input)
    {


        $user_id = $this->payload['user_id'];

        $user = User::query()->find($user_id);

        $action = $input['action'];
        $fee_type = $input['fee_type'];
        $fee = $input['fee'];
        $remark = $input['remark'];

        $target_type = AdminUser::class;
        $target_id = \Admin::user()->id;

        try {

            if (\Admin::user()->cannot("user-wallet-set")) {
                abort(400, "无权修改");
            }

            $walletService = new WalletService();
            if ($action == 1) {
                switch ($fee_type) {
                    case "balance":
                        $walletService->deposit($user, $fee, WalletType::balance, WalletLogSlug::other, WalletLogType::DepositSystem, $target_type, $target_id, function (Wallet $wallet, WalletLog $walletLog) use ($remark) {
                            if (!empty($remark)) {
                                $walletLog->mate()->updateOrCreate([], [
                                    'remark' => $remark,
                                ]);
                            }
                        });
                        break;
                    case "usdt_balance":
                        $walletService->deposit($user, $fee, WalletType::usdt, WalletLogSlug::other, WalletLogType::DepositSystem, $target_type, $target_id, function (Wallet $wallet, WalletLog $walletLog) use ($remark) {
                            if (!empty($remark)) {
                                $walletLog->mate()->updateOrCreate([], [
                                    'remark' => $remark,
                                ]);
                            }
                        });
                        break;
                    case "give_balance":
                        $walletService->deposit($user, $fee, WalletType::give, WalletLogSlug::other, WalletLogType::DepositSystem, $target_type, $target_id, function (Wallet $wallet, WalletLog $walletLog) use ($remark) {
                            if (!empty($remark)) {
                                $walletLog->mate()->updateOrCreate([], [
                                    'remark' => $remark,
                                ]);
                            }
                        });
                        break;
                }
            } else {
                switch ($fee_type) {
                    case "balance":
                        $walletService->withdraw($user, $fee, WalletType::balance, WalletLogSlug::other, WalletLogType::WithdrawSystem, $target_type, $target_id, function (Wallet $wallet, WalletLog $walletLog) use ($remark) {
                            if (!empty($remark)) {
                                $walletLog->mate()->updateOrCreate([], [
                                    'remark' => $remark,
                                ]);
                            }
                        });
                        break;
                    case "usdt_balance":
                        $walletService->withdraw($user, $fee, WalletType::usdt, WalletLogSlug::other, WalletLogType::WithdrawSystem, $target_type, $target_id, function (Wallet $wallet, WalletLog $walletLog) use ($remark) {
                            if (!empty($remark)) {
                                $walletLog->mate()->updateOrCreate([], [
                                    'remark' => $remark,
                                ]);
                            }
                        });
                        break;
                    case "give_balance":
                        $walletService->withdraw($user, $fee, WalletType::give, WalletLogSlug::other, WalletLogType::WithdrawSystem, $target_type, $target_id, function (Wallet $wallet, WalletLog $walletLog) use ($remark) {
                            if (!empty($remark)) {
                                $walletLog->mate()->updateOrCreate([], [
                                    'remark' => $remark,
                                ]);
                            }
                        });
                        break;
                }
            }
        } catch (\Exception $exception) {
            return $this->response()->error($exception->getMessage());
        }


        return $this
            ->response()
            ->success('操作成功')
            ->refresh();
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $user_id = $this->payload['user_id'];

        $user = User::query()->find($user_id);


        $this->display('用户ID')->default($user_id);
        $this->display('手机号码')->default($user->national_number);
        $this->display('当前余额')->default($user->wallet->balance);
        $this->display('USDT余额')->default($user->wallet->usdt_balance);
        $this->display('赠送金余额')->default($user->wallet->give_balance);

        $this->radio('action', '操作类型')->options([
            1 => '加款',
            2 => '扣款',
        ])->required();

        $this->radio('fee_type', '操作余额')->options([
            'balance' => '余额',
            'usdt_balance' => 'USDT',
            'give_balance' => '赠送金',
        ])->required();

        $this->text('fee', '变动金额')->required();

        $this->text('remark', '备注')->required();
    }

    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        return [
            'name' => 'John Doe',
            'email' => 'John.Doe@gmail.com',
        ];
    }
}
