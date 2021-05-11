<?php

namespace App\Enums;

use BenSampo\Enum\Enum;


final class UserAdTaskType extends Enum
{

    /**
     * 进行中
     */
    const InProgress = 'InProgress';
    /**
     * 已完成
     */
    const Finished = 'Finished';
    /**
     * 已过期
     */
    const HasExpired = 'HasExpired';
}
