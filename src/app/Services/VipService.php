<?php


namespace App\Services;


use App\Enums\WalletLogSlug;
use App\Enums\WalletLogType;
use App\Enums\WalletType;
use App\Models\Notifications\UserProductCommissionNotification;
use App\Models\Notifications\UserVipCommissionNotification;
use App\Models\User;
use App\Models\UserInviteAward;
use App\Models\UserVip;
use App\Models\Vip;
use App\Models\Wallet;
use App\Models\WalletLog;
use Carbon\Carbon;

class VipService extends BaseService
{

    public function getListOrm()
    {

        return Vip::query();

    }


    public function getUserVipList(User $user)
    {
        return $user->vips()->groupBy('level')->select(['level', \DB::raw('sum(task_num) as task_num_count'), 'vip_id'])->with('vip')->get();

    }

    public function getUserVipLevelTaskNum(User $user, int $level)
    {
        return $user->vips()->where('level','=', $level)->sum('task_num');
    }


    public function buyUserVip(User $user, Vip $vip, int $day, int $number)
    {

        abort_if($number <= 0, 400, Lang('ARGS_ERROR'));

        $day_money = $this->getMoneyByDay($vip, $day);

        abort_if($day_money <= 0, 400, Lang('ARGS_ERROR'));

        $vip_money = $day_money * $number;
        abort_if($vip_money <= 0, 400, Lang('ARGS_ERROR'));


        $fee = $vip_money;


        abort_if($vip->max_buy_num > 0 && $number > $vip->max_buy_num, 400, Lang('MAX_STACKS_EXCEEDED'));


        $userVip = null;

        WalletService::make()->withdraw($user, $fee, WalletType::balance, WalletLogSlug::buy, WalletLogType::WithdrawBuyVip, Vip::class, $vip->id, function (Wallet $wallet, WalletLog $walletLog) use ($day, $fee, $number, $vip_money, $vip, $user, &$userVip) {

            $task_num = $vip->task_num * $number;

            $userVip = UserVip::query()->create([
                'user_id' => $user->id,
                'vip_id' => $vip->id,
                'level' => $vip->level,
                'channel_id' => $user->channel_id,
                'link_id' => $user->link_id,
                'get_commission_type' => $vip->get_commission_type,
                'task_num' => $task_num,
                'buy_number_count' => $number,
                'buy_money_count' => $fee,
                'expire_time' => now()->addDays($day)
            ]);
        });

        UserHookService::make()->buyVipHook($user, $userVip, $number, $fee);
    }


    public function commissionHandle(User $user, UserVip $userVip, int $number, float $buy_money)
    {


        //??????????????????
        $user_invite = $user->invite;

        //????????????????????????
        $vip_money = $buy_money;

        $son_buy_vip_commission_config = $userVip->vip->son_buy_vip_commission_config;
        $walletService = new WalletService();

        $userInviteAward = UserInviteAward::query()->firstOrCreate(['user_id' => $user->id], [
            'channel_id' => $user->channel_id,
            'link_id' => $user->link_id,
        ]);

        for ($i = 1; $i <= 10; $i++) {
            //??????ID
            $invite_id = data_get($user_invite, 'invite_id_' . $i, 0);
            //??????????????????
            if ($invite_id <= 0) continue;
            //?????????????????????
            $invite_user = User::query()->find($invite_id);
            //??????????????????
            if (!$invite_user->status) continue;


            $invite_user_vip = $invite_user->vip;


            //????????????????????????
            $p_rate = (float)data_get($son_buy_vip_commission_config, "parent_" . $i . "_rate", 0);


            //????????????
            if ($p_rate <= 0) continue;
            //??????VIP????????????

            //????????????
            $all_fee = round($vip_money * ($p_rate / 100), 8);


            //???????????????VIP
            if (!$invite_user_vip) {
                $invite_user->notify(new UserVipCommissionNotification(0, $all_fee, true, false, $i, 0, $number, $buy_money, $user, $userVip));
                continue;
            }

            //????????????????????????
            $is_get_all_commission = true;

            //???????????????????????????VIP??????
            if (!$invite_user->isVipByLevel($userVip->level)) {
                $invite_user->notify(new UserVipCommissionNotification(0, $all_fee, true, false, $i, $invite_user_vip->level, $number, $buy_money, $user, $userVip));
                continue;
            }

            $invite_vip_money = $vip_money;


            //??????????????????VIP????????????
            /*$user_all_buy_money_count = $invite_user->vips()
                ->sum('buy_money_count');

            if ($invite_user_vip->get_commission_type === 0) {//????????????
                $invite_vip_money = $vip_money;
            } else {//???VIP??????????????????
                if ($user_all_buy_money_count >= $vip_money) {//??????????????????????????????
                    $invite_vip_money = $vip_money;
                } else {//????????????????????????
                    $invite_vip_money = $user_all_buy_money_count;
                    $is_get_all_commission = false;
                }
            }*/
            //????????????????????????
            if ($invite_vip_money <= 0) continue;

            //????????????
            $p_fee = round($invite_vip_money * ($p_rate / 100), 8);

            if ($p_fee <= 0) continue;

            $walletService->deposit($invite_user, $p_fee, WalletType::balance,
                WalletLogSlug::commission,
                WalletLogType::DepositFriendBuyProductCommission,
                UserVip::class, $userVip->id, function (Wallet $wallet, WalletLog $walletLog) {

                });
            //??????????????????
            $i_userInviteAward = UserInviteAward::query()->firstOrCreate(['user_id' => $invite_user->id], [
                'channel_id' => $invite_user->channel_id,
                'link_id' => $invite_user->link_id,
            ]);
            //?????????????????????
            $i_userInviteAward->increment('all_commission', $p_fee);
            //??????????????????????????????????????????
            $userInviteAward->increment('p_' . $i . '_commission', $p_fee);
            //??????????????????
            $invite_user->notify(new UserVipCommissionNotification($p_fee, $all_fee, false, $is_get_all_commission, $i, $invite_user_vip->level, $number, $buy_money, $user, $userVip));

        }


    }


    public function getMoneyByDay(Vip $vip, int $day): float
    {
        $day_data = collect($vip->day_money_data)->where('day', $day)->first();

        if ($day_data) {
            return (float)data_get($day_data, 'money', 0);
        }
        return 0;

    }

}
