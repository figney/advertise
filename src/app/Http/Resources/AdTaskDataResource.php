<?php

namespace App\Http\Resources;

use App\Models\AdTaskData;
use Illuminate\Http\Resources\Json\JsonResource;

class AdTaskDataResource extends JsonResource
{

    public function toArray($request)
    {
        /**@var AdTaskData|JsonResource $this */

        return [
            'title' => LocalDataGet($this->title),
            'describe' => $this->when($this->describe, LocalDataGet($this->describe)),
            'content' => $this->when($this->content, LocalDataGet($this->content)),
            'share_image' => $this->when($this->share_image, ImageUrl($this->share_image)),
            'share_content' => $this->when($this->share_content, LocalDataGet($this->share_content)),
        ];
    }
}
