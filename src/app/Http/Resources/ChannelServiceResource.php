<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChannelServiceResource extends JsonResource
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
            'url' => $this->url,
            'type' => $this->type,
            'name' => $this->name,
            'avatar' => ImageUrl($this->avatar),
        ];


    }
}
