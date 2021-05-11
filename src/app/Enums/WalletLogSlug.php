<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;


final class WalletLogSlug extends Enum implements LocalizedEnum
{



    /**
     * 充值
     */
    const recharge = "recharge";

    /**
     * 提现
     */
    const withdraw = "withdraw";

    /**
     * 利息
     */
    const interest = "interest";
    /**
     * 佣金
     */
    const commission = "commission";

    /**
     * 奖励
     */
    const award = "award";

    /**
     * 扣除
     */
    const deduct = "deduct";

    /**
     * 转换
     */
    const transform = "transform";

    /**
     * 存入
     * 从余额 存入 赚钱宝，投资产品
     */
    const deposit = "deposit";

    /**
     * 购买
     */
    const buy = "buy";

    /**
     * 取出
     * 从 赚钱宝，投资产品 取出到余额
     */

    const takeOut = "takeOut";

    /**
     * 退款
     */
    const refund = "refund";

    /**
     * 其他
     */
    const other = "other";
}
