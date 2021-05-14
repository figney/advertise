<?php


namespace App\Services\Pay;


use App\Models\RechargeChannel;
use App\Models\RechargeChannelList;
use App\Models\User;
use App\Models\UserRechargeOrder;
use App\Models\UserWithdrawOrder;
use App\Models\WithdrawChannel;

interface InPay
{
    public function withConfig(WithdrawChannel|RechargeChannel $channel);

    public function payIn(User $user, UserRechargeOrder $userRechargeOrder, ?RechargeChannelList $rechargeChannelList, string $redirect_url, ?string $son_code);

    public function payInBack($request);

    public function payOut(UserWithdrawOrder $userWithdrawOrder, WithdrawChannel $withdrawChannel);

    public function payOutBack($data);
}
