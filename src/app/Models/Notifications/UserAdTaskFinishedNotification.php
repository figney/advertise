<?php

namespace App\Models\Notifications;


use App\Models\UserAdTask;


class UserAdTaskFinishedNotification extends BaseNotification implements INotification
{


    public bool $forced = true;
    public bool $socket = true;

    public $title_slug = "广告任务完成奖励到账通知";
    public $content_slug = "广告任务完成奖励到账通知内容";

    public $type = "UserAdTaskFinishedNotification";


    public function __construct(
        protected float $fee,
        protected UserAdTask $userAdTask,
    )
    {

    }


    public function toArray(): array
    {
        return [];
    }


    public function toParams(): array
    {
        return [
            'fee' => MoneyFormat($this->fee),//获得的金额 0
            'user_ad_task_id' => $this->userAdTask->id,
            'ad_task_id' => $this->userAdTask->ad_task_id,
        ];
    }
}
