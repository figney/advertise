<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserAwardRecordResource extends JsonResource
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
            'level' => $this->level,
            'rate' => $this->rate,
            'amount' => $this->amount,
            'residue_amount' => $this->residue_amount,
            'deduct_count' => $this->deduct_count,
            'created_at' => TimeFormat($this->created_at),
            'son' => FriendResource::make($this->whenLoaded('son')),
        ];
    }
}
