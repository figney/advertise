<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FriendResource extends JsonResource
{


    public function toArray($request)
    {
        return [
            'name' => $this->name,
            'level' => $this->level,
            'zalo' => str_replace("+84", '0', $this->national_number),
            'created_at' => TimeFormat($this->created_at),
            'invite_award' => (float)data_get($this->whenLoaded('inviteAward'), 'p_' . $this->level . '_give_balance'),
            'invite_commission' => (float)data_get($this->whenLoaded('inviteAward'), 'p_' . $this->level . '_commission'),
        ];
    }
}
