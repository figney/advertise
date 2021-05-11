<?php


namespace App\Models\Notifications;


use App\Enums\NotificationType;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\UserWithdrawOrder;


class UserFriendDeductAwardNotification extends BaseNotification implements INotification
{


    public bool $forced = true;
    public bool $socket = true;

    public $title_slug = "下级提现扣除体验金通知";
    public $content_slug = "下级提现扣除体验金通知内容";

    public $type = "UserFriendDeductAwardNotification";

    /**
     * TestNotification constructor.
     * @param $fee
     */
    public function __construct(protected UserWithdrawOrder $userWithdrawOrder, protected float $deduct_fee)
    {

    }


    public function toArray(): array
    {
        return [];
    }

    public function toParams(): array
    {
        return [
            'fee' => $this->userWithdrawOrder->amount,//提醒金额
            'wallet_type_lang' => $this->userWithdrawOrder->wallet_type,//钱包类型
            'deduct_fee' => $this->deduct_fee,//扣除金额
        ];
    }
}
