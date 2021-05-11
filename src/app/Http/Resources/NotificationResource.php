<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            'id' => $this->_id,
            'socket' => $this->socket,
            'forced' => $this->forced,
            'type' => $this->type,
            'title' => $this->title(),
            'content' => $this->content(),
            'data' => $this->data,
            'params' => $this->params,
            'read' => $this->read(),
            'created_at' => TimeFormat($this->created_at),
        ];
    }
}
