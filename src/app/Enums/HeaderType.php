<?php

namespace App\Enums;

use BenSampo\Enum\Enum;


final class HeaderType extends Enum
{

    const IMEI = "IMEI";
    const Lang = "Lang";
    const Version = "Version";
    const BrowserName = "BrowserName";
    const BrowserVersion = "BrowserVersion";
    const Brand = "Brand";
    const Model = "Model";
    const Width = "Width";
    const Height = "Height";
    const IsApp = "IsApp";
    const Os = "Os";
    const Timezone = "Timezone";
}
