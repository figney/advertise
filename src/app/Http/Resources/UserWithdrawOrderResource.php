<?php

namespace App\Http\Resources;

use App\Enums\WithdrawOrderStatusType;
use App\Models\UserWithdrawOrder;
use Illuminate\Http\Resources\Json\JsonResource;

class UserWithdrawOrderResource extends JsonResource
{

    public function toArray($request)
    {
        /**@var UserWithdrawOrder|JsonResource $this */
        return [
            'order_sn' => $this->order_sn,
            'wallet_type' => $this->wallet_type,
            'amount' => (float)$this->amount,
            'actual_amount' => (float)$this->actual_amount,
            'rate' => (float)$this->rate,
            'rate_amount' => (float)$this->rate_amount,
            'withdraw_type' => $this->withdraw_type,
            'input_data' => $this->input_data,
            'remark' => $this->remarkContent(),
            'order_status' => WithdrawOrderStatusType::fromValue($this->order_status)->key,
            'pay_time' => $this->pay_time,
            'is_pay' => $this->is_pay,
            'created_at' => TimeFormat($this->created_at),
            'withdraw_channel' => WithdrawChannelResource::make($this->whenLoaded('withdrawChannel')),
            'withdraw_channel_item' => WithdrawChannelListResource::make($this->whenLoaded('withdrawChannelItem')),
        ];
    }
}
