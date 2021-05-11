<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;


class ChannelService extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'channel_service';

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function updateUserCount()
    {
        $count = User::query()->where('channel_service_id', $this->id)->count();
        $this->user_count = $count;
        $this->save();
    }


    protected static function booted()
    {
        static::deleting(function (ChannelService $channelService) {

            abort_if($channelService->user_count > 0, 400, "已绑定用户的客服无法删除");
        });
    }

}
