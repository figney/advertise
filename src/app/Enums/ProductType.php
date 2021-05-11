<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;


final class ProductType extends Enum implements LocalizedEnum
{
    //余额
    const balance = "balance";
    //USDT
    const usdt = "usdt";

}
