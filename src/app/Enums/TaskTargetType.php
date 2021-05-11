<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class TaskTargetType extends Enum implements LocalizedEnum
{
    /**
     * 首次
     */
    const First = 'First';

    /**
     *  每次
     */
    const Every = 'Every';

    /**
     * 完成/累计/到达
     */
    const Accomplish = 'Accomplish';

    /**
     * 连续N天
     */
    const ContinuousDay = "ContinuousDay";
    /**
     * 连续N天递增
     */
    const ContinuousDayIncrease = "ContinuousDayIncrease";

}
