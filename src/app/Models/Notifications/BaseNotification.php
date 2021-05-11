<?php

namespace App\Models\Notifications;


class BaseNotification
{

    /**
     * 消息类型
     * @var string
     */
    public $type = "";
    /**
     * 是否socket.io 推送
     * @var bool
     */
    public bool $socket = false;
    /**
     * 是否强制提醒
     * @var bool
     */
    public bool $forced = false;

    /**
     * 通知标题语言标识
     * @var string
     */
    public $title_slug = "";

    /**
     * 通知内容语言标识
     * @var string
     */
    public $content_slug = "";


}
