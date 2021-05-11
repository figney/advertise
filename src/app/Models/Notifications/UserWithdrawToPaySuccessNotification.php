<?php


namespace App\Models\Notifications;


use App\Enums\NotificationType;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\UserWithdrawOrder;


class UserWithdrawToPaySuccessNotification extends BaseNotification implements INotification
{


    public bool $forced = false;
    public bool $socket = true;

    public $title_slug = "提现打款成功通知";
    public $content_slug = "提现打款成功通知内容";

    public $type = "UserWithdrawToPaySuccessNotification";


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
            'amount' => $this->userWithdrawOrder->amount,
            'wallet_type_lang' => $this->userWithdrawOrder->wallet_type,
            'actual_amount' => $this->userWithdrawOrder->actual_amount,
            'remark' => $this->userWithdrawOrder->remarkContent($this->local)
        ];
    }
}
