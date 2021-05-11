<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;


final class WalletType extends Enum implements LocalizedEnum
{

    //余额
    const balance = "balance";
    //USDT
    const usdt = "usdt";
    //比特币
    //const btc = "btc";
    //以太币
    //const eth = "eth";
    //赠送金
    const give = "give";
}
