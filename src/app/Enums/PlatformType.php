<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;


final class PlatformType extends Enum implements LocalizedEnum
{
    const PayTM = 'PayTM';
    const IPayIndian = 'IPayIndian';
    const LaoSun = 'LaoSun';
    const FPay = 'FPay';
    const Yudrsu = 'Yudrsu';
    const JstPay = 'JstPay';
    const BananaPay = 'BananaPay';
    const IvnPay = 'IvnPay';
    const HaoDaMallPay = 'HaoDaMallPay';
    const PayPlus = 'PayPlus';
    const Other = 'Other';

}
