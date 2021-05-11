<?php


namespace App\Models\Notifications;


use App\Http\Resources\TaskNotificaltionResource;
use App\Models\Task;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\UserVip;
use Illuminate\Support\Str;


class UserVipCommissionNotification extends BaseNotification implements INotification
{


    public bool $forced = true;
    public bool $socket = true;

    public $title_slug = "VIP佣金到账通知";
    public $content_slug = "VIP佣金到账通知内容";

    public $type = "UserVipCommissionNotification";


    public function __construct(
        protected float $fee,
        protected float $all_fee,
        protected bool $is_no_commission,
        protected bool $is_get_all_commission,
        protected int $level,
        protected int $my_vip_level,
        protected int $buy_number,
        protected float $buy_money,
        protected User $buyUser,
        protected UserVip $userVip,
    )
    {

        if ($this->is_no_commission) {
            $this->title_slug = "VIP佣金无法获得通知";
            $this->content_slug = "VIP佣金无法获得通知内容";
        }

        if (!$this->is_get_all_commission && !$this->is_no_commission) {
            $this->title_slug = "VIP佣金部分到账通知";
            $this->content_slug = "VIP佣金部分到账通知内容";
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
            'my_vip_level' => $this->my_vip_level,//我的VIP层级 3
            'my_vip_buy_money_count' => 0,//我的VIP有效购买金额 4
            'buy_vip_level' => $this->userVip->level,//5 下线购买的VIP等级
            'buy_number' => $this->buy_money,//6 下线购买的叠加数
            'buy_money' => $this->buy_money,//7 下线本次购买消费金额
            'is_no_commission' => $this->is_no_commission,//是否未能获得佣金 8
            'is_get_all_commission' => $this->is_get_all_commission,//是否能获得全部佣金 9
        ];
    }
}
