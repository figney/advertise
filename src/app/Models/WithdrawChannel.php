<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WithdrawChannel extends Model
{
    use HasDateTimeFormatter,Cachable;

    protected $table = 'withdraw_channels';

    protected $casts = [
        'status' => 'boolean',
        'select_bank' => 'boolean',
        'title' => 'json',
        'config' => 'json',
        'input_config' => 'json',
        'remark' => 'json',
    ];


    public function channelList()
    {
        return $this->hasMany(WithdrawChannelList::class)->orderByDesc('order');
    }

    /**
     * 获取渠道配置
     * @param $key
     * @return mixed
     */
    public function configValue($key)
    {
        return collect($this->config)->pluck('value', 'key')->get($key);
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

    public function updateMinAdnMaxMoney()
    {
        if ($this->channelList()->count() > 0) {
            $min = $this->channelList()->min('min_money');
            $max = $this->channelList()->min('max_money');
            $this->min_money = $min;
            $this->max_money = $max;
            $this->save();
        }


    }
}
