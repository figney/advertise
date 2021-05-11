<?php

namespace App\Http\Resources;

use App\Models\AdTask;
use Illuminate\Http\Resources\Json\JsonResource;
use function Aws\boolean_value;

class AdTaskResource extends JsonResource
{

    protected bool $isList = false;

    public function toArray($request)
    {

        /**@var AdTask|JsonResource $this */

        return [
            'id' => $this->id,
            'vip_level' => $this->vip_level,
            'money' => (float)$this->money,
            'complete_click_number' => (int)$this->complete_click_number,
            'total' => $this->total,
            'rest' => $this->rest,
            'valid_hour' => $this->valid_hour,
            'tags' => $this->tags,
            'icon' => ImageUrl($this->icon),
            'overdue_return' => boolval($this->overdue_return),
            'data' => AdTaskDataResource::make($this->whenLoaded('adData')),
            'user_ad_task' => $this->when(in_array('userAdTask', collect($this->getRelations())->keys()->toArray()), UserAdTaskResource::make($this->userAdTask)),
        ];
    }


}
