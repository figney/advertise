<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserVipResource extends JsonResource
{

    public function toArray($request): array
    {
        return [
            'level' => $this->level,
            'task_num' => (int)$this->task_num_count,
            'vip_info' => VipBaseResource::make($this->whenLoaded('vip')),
        ];
    }
}
