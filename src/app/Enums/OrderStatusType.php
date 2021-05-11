<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class OrderStatusType extends Enum implements LocalizedEnum
{

    /**
     * 支付中
     */
    const Paying = 0;

    /**
     * 支付成功
     */

    const PaySuccess = 1;

    /**
     * 支付失败
     */
    const PayError = 2;

    /**
     * 订单关闭
     */
    const Close = 3;
}
