<?php


namespace App\Services;


use App\Models\ChannelLink;
use App\Models\User;

class ChannelService extends BaseService
{

    //获取用户客服
    public function getUserService()
    {
        try {
            //如果登录
            $user = $this->user();
            if ($user) {
                //是否已绑定客服
                $cs = $user->channelService;
                if (!$cs) {
                    //绑定客服
                    $cs = $this->getChannelService($user);
                    //没有客服
                    abort_if(!$cs, 400, "无法获取客服");
                    if ($cs && $user->channel_service_id <= 0) {
                        $cs->increment('user_count');
                        $user->bindChannelService($cs);
                    }
                }
                return $cs;
            } else {
                //未登录
                $link_id = (int)request('link_id');
                $channel_id = (int)request('channel_id');

                return \App\Models\ChannelService::query()->where('status', true)->where('channel_id', 1)->inRandomOrder()->first();
            }
        } catch (\Exception $exception) {
            //给一个默认客服
            return \App\Models\ChannelService::query()->where('status', true)->where('channel_id', 1)->inRandomOrder()->first();
        }
    }

    //随机获取渠道客服
    public function getChannelService(User $user)
    {
        if ($user->link_id > 0) {
            $link_channel_service_id = ChannelLink::query()->where('id', $user->link_id)->value('channel_service_id');
            if ($link_channel_service_id > 0) {
                return \App\Models\ChannelService::query()->where('status', true)->find($link_channel_service_id);
            }
        }

        if ($user->channel_id > 0) {
            return \App\Models\ChannelService::query()->where('status', true)->where('channel_id', $user->channel_id)->inRandomOrder()->first();
        }
        return \App\Models\ChannelService::query()->where('status', true)->where('channel_id', 1)->inRandomOrder()->first();
    }

}
