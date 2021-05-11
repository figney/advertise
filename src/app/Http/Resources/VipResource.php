<?php

namespace App\Http\Resources;

use App\Models\Vip;
use Illuminate\Http\Resources\Json\JsonResource;

class VipResource extends JsonResource
{

    public function toArray($request)
    {
        /**@var Vip|JsonResource $this */
        return [
            'id' => $this->id,
            'level' => $this->level,
            'title' => LocalDataGet($this->title),
            'describe' => LocalDataGet($this->describe),
            'icon' => ImageUrl($this->icon),
            'bg_image' => ImageUrl($this->bg_image),
            'task_num' => (int)$this->task_num,
            'task_num_money_list' => $this->day_money_data,
            'attrs' => $this->attrs,
            'dev_config' => collect($this->dev_config)->pluck('value', 'key'),
        ];
    }
}
