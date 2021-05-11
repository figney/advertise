<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Channel extends Model
{
    use HasDateTimeFormatter;


    public static function options(): \Illuminate\Support\Collection
    {
        return self::query()->pluck('name', 'id');
    }

    public function domains()
    {
        return $this->hasMany(Domain::class)->where('status', 1)->where('type', 'ad');
    }


    public function admin()
    {
        return $this->belongsTo(AdminUser::class, 'admin_id');
    }

    public function channelService()
    {
        return $this->belongsTo(ChannelService::class);
    }

    public function address()
    {
        return collect($this->domains)->map(function ($domain) {

            $url = Str::finish($domain->domain, '/');

            $url .= "?ch={$this->id}&s=ad";

            return $url;

        })->toArray();
    }

    protected static function booted()
    {
        static::deleting(function (Channel $item) {
            if ($item->id === 1) {
                abort(400, "无法删除");
            }
        });
    }
}
