<?php

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskNotificaltionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /**@var Task|JsonResource $this * */
        return [
            'id' => $this->id,
            'icon' => ImageUrl($this->icon),
            'title' => LocalDataGet($this->title),
            'describe' => LocalDataGet($this->describe),
            'btn_name' => LocalDataGet($this->btn_name),
        ];
    }
}
