<?php


namespace App\Models\Notifications;


use App\Enums\NotificationType;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\UserWithdrawOrder;


class UserWithdrawRejectNotification extends BaseNotification implements INotification
{


    public bool $forced = false;
    public bool $socket = true;

    public $title_slug = "提现申请失败通知";
    public $content_slug = "提现申请失败通知内容";

    public $type = "UserWithdrawRejectNotification";


    public function __construct(protected UserWithdrawOrder $userWithdrawOrder, protected string $local)
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
            'amount' => (float)$this->userWithdrawOrder->amount,
            'actual_amount' => (float)$this->userWithdrawOrder->actual_amount,
            'remark' => $this->userWithdrawOrder->remarkContent($this->local)
        ];
    }
}
