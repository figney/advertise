<?php

namespace App\Http\Resources;

use App\Models\UserAdTask;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAdTaskResource extends JsonResource
{

    public function toArray($request)
    {

        /**@var UserAdTask|JsonResource $this */

        return [
            'id' => $this->id,
            'ad_task_id' => $this->ad_task_id,
            'now_click_number' => $this->now_click_number,
            'complete_click_number' => $this->complete_click_number,
            'expired_time' => Carbon::make($this->expired_time)->floatDiffInSeconds(now()),
            'created_at' => $this->created_at,
            'status' => $this->status,
            'money' => (float)$this->money,
            'url' => $this->getAdTaskUrl(),
            'ad_task' => AdTaskResource::make($this->whenLoaded('adTask')),
        ];
    }
}
