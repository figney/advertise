<?php

namespace App\Enums;

use BenSampo\Enum\Enum;


final class ChannelServiceType extends Enum
{
    const WhatsApp =   "WhatsApp";
    const Line =   "Line";
    const FB =   "FB";
    const Other =   "Other";
}
