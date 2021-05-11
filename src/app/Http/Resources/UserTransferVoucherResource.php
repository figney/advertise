<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserTransferVoucherResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'image' => ImageUrl($this->image),
            'user_name' => $this->user_name,
            'card_number' => $this->card_number,
            'bank_name' => $this->bank_name,
            'amount' => (float)$this->amount,
            'time' => $this->time,
            'status' => $this->status,
            'check_type' => $this->check_type,
            'check_info' => Lang($this->check_slug),
            'check_time' => $this->check_time,
            'channel_item' => RechargeChannelListResource::make($this->whenLoaded('channelItem')),
        ];
    }
}
