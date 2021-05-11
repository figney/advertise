<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
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
            'id'=>$this->id,
            'title' => LocalDataGet($this->title),
            'describe' => LocalDataGet($this->describe),
            'image' => ImageUrl(LocalDataGet($this->image)),
            'link_name' => LocalDataGet($this->link_name),
            'link' => $this->link,
        ];
    }
}
