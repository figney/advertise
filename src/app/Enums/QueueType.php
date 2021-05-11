<?php

namespace App\Enums;

use BenSampo\Enum\Enum;


final class QueueType extends Enum
{
    /**
     * 默认
     */
    const default = "default";
    /**
     * 数据统计
     */
    const statistics = "statistics";
    /**
     * 发送数据
     */
    const send = "send";
    const allSend = "allSend";
    /**
     * 请求数据
     */
    const request = "request";
    /**
     * 后台
     */
    const admin = "admin";
    /**
     * 用户
     */
    const user = "user";

    /**
     * 赚钱宝
     */
    const moneyBao = "moneyBao";

    /**
     * 投资产品
     */
    const product = "product";


}
