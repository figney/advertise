<?php

namespace App\Http\Resources;

use App\Models\WithdrawChannel;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawChannelResource extends JsonResource
{

    public function toArray($request)
    {

        /** @var WithdrawChannel $this * */

        return [
            'id' => $this->id,
            'min_money' => (float)$this->min_money,
            'max_money' => (float)$this->max_money,
            'rate' => (float)$this->rate,
            'type' => $this->type,
            'select_bank' => $this->select_bank,
            'status' => $this->status,
            'title' => LocalDataGet($this->title),
            'cover' => ImageUrl($this->cover),
            'star_hour' => $this->star_hour,
            'end_hour' => $this->end_hour,
            'remark' => LocalDataGet($this->remark),
            'input_config' => $this->inputConfigData(),
            'channel_list' => WithdrawChannelListResource::collection($this->whenLoaded('channelList')),
        ];
    }
}
