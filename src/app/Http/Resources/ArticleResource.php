<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
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
            'slug' => $this->slug,
            'order' => $this->order,
            'cover' => ImageUrl($this->cover),
            'title' => $this->title,
            'describe' => $this->describe,
            'content' => $this->content,
        ];
    }
}
