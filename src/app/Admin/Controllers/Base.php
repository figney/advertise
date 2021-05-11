<?php


namespace App\Admin\Controllers;


use App\Models\Channel;
use App\Models\ChannelService;

trait Base
{
    protected function adminID()
    {
        return \Admin::user()->id;
    }

    protected function isChannel()
    {
        return \Admin::user()->isRole('channel');
    }

    protected function isAdministrator()
    {
        return \Admin::user()->isAdministrator();
    }

    protected function getChannelID()
    {
        $id = Channel::query()->where('admin_id', $this->adminID())->value('id');

        abort_if(!$id, 400, "当前用户未绑定渠道");

        return $id;
    }

    protected function getChannelIds()
    {
        $id = Channel::query()->where('admin_id', $this->adminID())->value('id');
        if ($id) {
            return Channel::query()->where('id', $id)->orWhere('parent_id', $id)->pluck('id')->toArray();
        }
        return [-1];
    }

    protected function getChannelServiceModel()
    {

        $orm = ChannelService::query();

        if (!$this->isAdministrator()) {
            $orm->whereIn('channel_id', $this->getChannelIds());
        }

        return $orm;

    }

}
