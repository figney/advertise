<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawChannelListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'withdraw_channel_id' => $this->withdraw_channel_id,
            'name' => $this->name,
            'bank_name' => $this->bank_name,
            'bank_code' => $this->bank_code,
            'bank_cover' => ImageUrl($this->bank_cover),
            'min_money' => (float)$this->min_money,
            'max_money' => (float)$this->max_money,
            'input_config' => $this->inputConfigData(),
            'remark' => LocalDataGet($this->remark),
        ];
    }
}
