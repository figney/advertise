<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{

    public function toArray($request): array
    {
        /** @var User $this */

        return [
            'id' => (int)$this->id,
            'c_id' => $this->channel_id,
            'l_id' => $this->link_id,
            'hash' => $this->hash,
            'name' => $this->name,
            'activity' => (boolean)$this->activity,
            'has_recharge' => $this->hasRecharge(),//是否充值
            'day_interest' => $this->day_interest,//今日昨日收入
            'unread_notifications_count' => $this->unreadNotifications()->count(),//未读消息
            'parent' => FriendResource::make($this->whenLoaded('parent')),//上级数据
            'invite' => UserInviteResource::make($this->whenLoaded('invite')),//邀请数据
            'invite_award' => (float)data_get($this->whenLoaded('inviteAward'), 'give_balance'),//下线总收益
            'invite_commission' => (float)data_get($this->whenLoaded('inviteAward'), 'all_commission'),
            'wallet' => UserWalletResource::make($this->whenLoaded('wallet')),//钱包数据
            'vip' => UserVipResource::collection($this->whenLoaded('vips')),//VIP数据
            //'money_bao' => UserMoneyBaoResource::make($this->whenLoaded('moneyBao')),//赚钱宝数据
            //'product_data' => $this->productData(),//产品数据
            //'all_property' => $this->all_property, //累计资产
            'created_at' => $this->created_at,
            'ad_task_data' => $this->adTaskData(),
        ];


    }
}
