<?php


namespace App\Models\Notifications;


use App\Models\UserWithdrawOrder;


class UserWithdrawRefundNotification extends BaseNotification implements INotification
{


    public bool $forced = false;
    public bool $socket = true;

    public $title_slug = "提现订单退款通知";
    public $content_slug = "提现订单退款内容";

    public $type = "UserWithdrawRefundNotification";


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
            'amount' => $this->userWithdrawOrder->amount,
        ];
    }
}
