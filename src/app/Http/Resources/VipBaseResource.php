<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VipBaseResource extends JsonResource
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
            'title' => LocalDataGet($this->title),
            //'describe' => LocalDataGet($this->describe),
            'icon' => ImageUrl($this->icon),
            //'bg_image' => ImageUrl($this->bg_image),
           //'forever' => (boolean)$this->forever,
            //'forever_money' => (float)$this->forever_money,
            //'day_money' => $this->day_money_data,
            //'attrs' => $this->attrs,
            //'dev_config' => collect($this->dev_config)->pluck('value', 'key'),
        ];
    }
}
