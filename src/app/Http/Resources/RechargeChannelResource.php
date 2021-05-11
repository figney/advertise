<?php

namespace App\Http\Resources;

use App\Models\RechargeChannel;
use Illuminate\Http\Resources\Json\JsonResource;

class RechargeChannelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var RechargeChannel $this * */
        return [
            'id' => $this->id,
            'min_money' => (float)$this->min_money,
            'max_money' => (float)$this->max_money,
            'type' => $this->type,
            'select_bank' => $this->select_bank,
            'title' => LocalDataGet($this->title),
            'cover' => ImageUrl($this->cover),
            'remark' => LocalDataGet($this->remark),
            'channel_list' => RechargeChannelListResource::collection($this->whenLoaded('channelList')),
        ];
    }
}
