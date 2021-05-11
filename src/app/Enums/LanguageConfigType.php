<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;


final class LanguageConfigType extends Enum implements LocalizedEnum
{
    const default = "default";
    const client = "client";
    const serve = "serve";
}
