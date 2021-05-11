<?php


namespace App\Models\Notifications;


use App\Enums\NotificationType;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\UserWithdrawOrder;


class UserWithdrawToPayNotification extends BaseNotification implements INotification
{


    public bool $forced = false;
    public bool $socket = true;

    public $title_slug = "提现打款中通知";
    public $content_slug = "提现打款中通知内容";

    public $type = "UserWithdrawToPayNotification";


    public function __construct(protected UserWithdrawOrder $userWithdrawOrder)
    {

    }


    public function toArray(): array
    {
        return [];
    }

    public function toParams(): array
    {
        return [
            'order_sn' => $this->userWithdrawOrder->order_sn,
            'actual_amount' => $this->userWithdrawOrder->actual_amount,
        ];
    }
}
