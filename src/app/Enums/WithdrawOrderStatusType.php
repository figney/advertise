<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class WithdrawOrderStatusType extends Enum implements LocalizedEnum
{

    /**
     * 审核中
     */
    const Checking = 0;

    /**
     * 审核成功
     */

    const CheckSuccess = 1;

    /**
     * 审核失败
     */
    const CheckError = 2;

    /**
     * 失败并退款
     */
    const CheckErrorAndRefund = 4;

    /**
     * 打款中
     */
    const Paying = 5;

    /**
     * 打款失败
     */
    const PayError = 6;

    /**
     * 关闭
     */
    const Close = 3;
}
