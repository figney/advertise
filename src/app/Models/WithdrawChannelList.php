<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WithdrawChannelList extends Model
{
    use HasDateTimeFormatter,Cachable;

    protected $table = 'withdraw_channel_list';

    protected $guarded = [];

    protected $casts = [
        'input_config' => 'json',
        'remark' => 'json',
    ];

    public function withdrawChannel(): BelongsTo
    {
        return $this->belongsTo(WithdrawChannel::class);
    }


    public function inputConfigSlug($name)
    {
        return collect($this->input_config)->pluck('slug', 'name')->get($name);
    }


    public function inputConfigData()
    {

        if ($this->input_config) {

            return collect($this->input_config)->map(function ($item) {
                return [
                    'name' => $item['name'],
                    'label' => Lang(Str::upper($this->inputConfigSlug($item['name']))),
                    'value' => '',
                ];
            })->toArray();
        }
        return [];


    }

    protected static function booted()
    {

        static::created(function (WithdrawChannelList $withdrawChannelList) {
            $withdrawChannelList->withdrawChannel->updateMinAdnMaxMoney();
        });

        static::updated(function (WithdrawChannelList $withdrawChannelList) {
            $withdrawChannelList->withdrawChannel->updateMinAdnMaxMoney();
        });
    }
}
