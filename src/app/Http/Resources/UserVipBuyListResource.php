<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserVipBuyListResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'created_at' => $this->created_at,
            'level' => $this->level,
            'task_num' => $this->task_num,
            'expire_time' => $this->expire_time,
            'vip_info' => VipBaseResource::make($this->whenLoaded('vip')),
        ];
    }
}
