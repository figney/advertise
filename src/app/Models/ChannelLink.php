<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class ChannelLink extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'channel_links';

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function channelService()
    {
        return $this->belongsTo(ChannelService::class);
    }

}
