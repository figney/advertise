<?php

namespace App\Enums;

use BenSampo\Enum\Enum;


final class NextActionType extends Enum
{
    const Wallet = 'Wallet';
    const Money = 'Money';
    const Product = 'Product';
}
