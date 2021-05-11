<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;


final class TransferVoucherCheckType extends Enum implements LocalizedEnum
{
    /**
     * 审核中
     */
    const UnderReview = "UnderReview";

    /**
     * 驳回
     */
    const Reject = "Reject";

    const Pass= "Pass";

}
