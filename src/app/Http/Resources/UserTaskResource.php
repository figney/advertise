<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserTaskResource extends JsonResource
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
            'achieve_count' => $this->achieve_count,
            'achieve_time' => $this->achieve_time,
            'last_time' => $this->last_time,
            'next_time' => $this->next_time,
            'repetition' => (bool)$this->repetition,
            'target_condition' => (float)$this->target_condition,
            'ut_continuous_day' => $this->ut_continuous_day,
            'last_achieve_time' => TimeFormat($this->last_achieve_time),
            'day_achieve'=>$this->day_achieve,
        ];
    }
}
