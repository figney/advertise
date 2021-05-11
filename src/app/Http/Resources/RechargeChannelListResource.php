<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RechargeChannelListResource extends JsonResource
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
            'card_user_name' => $this->card_user_name,
            'card_number' => $this->card_number,
            'card_bank_name' => $this->card_bank_name,
            'bank_cover' => ImageUrl($this->bank_cover),
            'bank_name' => $this->bank_name,
            'bank_code' => $this->bank_code,
            'min_money' => (float)$this->min_money,
            'max_money' => (float)$this->max_money,
            'son_bank_list' => collect((array)$this->son_bank_list)->filter(fn($i) => data_get($i, 'status', 0) == 1)->toArray(),
        ];
    }
}
