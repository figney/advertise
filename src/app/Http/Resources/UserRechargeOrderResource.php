<?php

namespace App\Http\Resources;

use App\Enums\OrderStatusType;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRechargeOrderResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'order_sn' => $this->order_sn,
            'wallet_type' => $this->wallet_type,
            'amount' => (float)$this->amount,
            'recharge_type' => $this->recharge_type,
            'next_action' => $this->next_action,
            'next_id' => $this->next_id,
            'is_pay' => $this->is_pay,
            'pay_time' => $this->pay_time,
            'order_status' => OrderStatusType::getKey($this->order_status),
            'created_at' => TimeFormat($this->created_at),
            'remark' => $this->remarkContent(),
        ];
    }
}
