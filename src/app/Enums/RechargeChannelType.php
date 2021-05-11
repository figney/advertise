<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;


final class RechargeChannelType extends Enum implements LocalizedEnum
{
    /**
     * USDT_TRC20
     */
    const USDT_TRC20 = 'USDT_TRC20';
    /**
     * 在线支付
     */
    const OnLine = 'OnLine';
    /**
     * 银行卡转账
     */
    const TransferAccounts = "TransferAccounts";
}
