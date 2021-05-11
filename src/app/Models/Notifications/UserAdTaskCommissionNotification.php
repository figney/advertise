<?php


namespace App\Models\Notifications;


use App\Models\User;
use App\Models\UserAdTask;


class UserAdTaskCommissionNotification extends BaseNotification implements INotification
{


    public bool $forced = true;
    public bool $socket = true;

    public $title_slug = "广告任务佣金到账通知";
    public $content_slug = "广告任务佣金到账通知内容";

    public $type = "UserAdTaskCommissionNotification";


    public function __construct(
        protected float $fee,
        protected float $all_fee,
        protected bool $is_no_commission,
        protected bool $is_get_all_commission,
        protected int $level,
        protected int $ad_task_level,
        protected int $my_vip_level,
        protected User $sonUser,
        protected UserAdTask $userAdTask,
    )
    {

        if ($this->is_no_commission) {
            $this->title_slug = "广告任务佣金无法获得通知";
            $this->content_slug = "广告任务佣金无法获得通知内容";
        }

        if (!$this->is_get_all_commission && !$this->is_no_commission) {
            $this->title_slug = "广告任务佣金部分到账通知";
            $this->content_slug = "广告任务佣金部分到账通知内容";
        }

    }


    public function toArray(): array
    {
        return [];
    }


    public function toParams(): array
    {
        return [
            'fee' => MoneyFormat($this->fee),//获得的佣金 0
            'all_fee' => MoneyFormat($this->all_fee),//总佣金 1
            'level' => $this->level,//下线层级 2
            'ad_task_level' => $this->ad_task_level,//广告VIP等级 3
            'my_vip_level' => $this->my_vip_level,//我的VIP等级 4
            'is_no_commission' => $this->is_no_commission,//是否未能获得佣金 5
            'is_get_all_commission' => $this->is_get_all_commission,//是否能获得全部佣金 6
            'buy_user_name' => $this->sonUser->name,//下级的昵称 7
        ];
    }
}
