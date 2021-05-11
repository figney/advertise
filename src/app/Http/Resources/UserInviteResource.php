<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserInviteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'total'=>$this->total_all,
            'total_1'=>$this->total_1,
            'total_2'=>$this->total_2,
            'total_3'=>$this->total_3,
            'total_4'=>$this->total_4,
            'total_5'=>$this->total_5,
            'total_6'=>$this->total_6,
            'total_7'=>$this->total_7,
            'total_8'=>$this->total_8,
            'total_9'=>$this->total_9,
            'total_10'=>$this->total_10,
        ];
    }
}
