<?php

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
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
            'hook' => $this->hook,
            'task_target' => $this->task_target,
            'continuous_day' => $this->continuous_day,
            'target_condition' => (float)$this->target_condition,
            'start_amount' => (float)$this->start_amount,
            'increase_amount' => (float)$this->increase_amount,
            'check_withdraw' => $this->check_withdraw,
            'repetition' => $this->repetition,
            'is_user_award' => (bool)$this->is_user_award,
            'user_award_rate' => (float)$this->user_award_rate,
            'user_award_amount' => (float)$this->user_award_amount,
            'is_deduct' => (bool)$this->is_deduct,
            'deduct_rate' => (float)$this->deduct_rate,
            'is_show_alert' => $this->is_show_alert,
            'total_award_amount' => (float)$this->total_award_amount,
            'icon' => ImageUrl($this->icon),
            'title' => LocalDataGet($this->title),
            'describe' => LocalDataGet($this->describe),
            'btn_name' => LocalDataGet($this->btn_name),
            'content' => LocalDataGet($this->content),
            'start_time' => $this->start_time ?? $this->created_at,
            'end_time' => $this->end_time,
            'day_max' => $this->day_max,
            'user_task' => UserTaskResource::make($this->whenLoaded('userTask')),

        ];
    }
}
