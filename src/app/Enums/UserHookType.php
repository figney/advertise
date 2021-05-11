<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;


final class UserHookType extends Enum implements LocalizedEnum
{
    /**
     * 注册
     */
    const Register = 'Register';

    /**
     * 登录
     */
    const Login = 'Login';
    /**
     * 签到
     */
    const Sign = 'Sign';
    /**
     * 邀请
     */
    const Invite = 'Invite';
    /**
     * 充值
     */
    const Recharge = 'Recharge';
    /**
     * 提现
     */
    const Withdraw = 'Withdraw';
    /**
     * 利息收益
     */
    const Earnings = 'Earnings';

    /**
     * APP
     */
    const APP = 'APP';

    const BuyProduct = 'BuyProduct';
}
