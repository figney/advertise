<?php


namespace App\Models\Notifications;


use App\Http\Resources\UserRechargeOrderResource;
use App\Models\UserRechargeOrder;
use Illuminate\Support\Str;

class RechargeOrderSuccessNotification extends BaseNotification implements INotification
{


    public bool $forced = true;
    public bool $socket = true;

    public $title_slug = "充值到账通知";
    public $content_slug = "充值到账通知内容";

    public $type = "RechargeOrderSuccessNotification";


    protected UserRechargeOrder $userRechargeOrder;


    public function __construct(UserRechargeOrder $userRechargeOrder)
    {
        $this->userRechargeOrder = $userRechargeOrder;
    }

    public function toArray(): array
    {
        return json_decode(UserRechargeOrderResource::make($this->userRechargeOrder)->toJson(), true);//充值订单对象
    }

    public function toParams(): array
    {
        return [
            'amount' => $this->userRechargeOrder->amount,
            'wallet_type_lang' => $this->userRechargeOrder->wallet_type
        ];
    }
}
