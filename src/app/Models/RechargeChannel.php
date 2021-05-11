<?php

namespace App\Models;

use Dcat\Admin\Grid\Displayers\Orderable;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;


class RechargeChannel extends Model implements Sortable
{
    use HasDateTimeFormatter, SortableTrait;

    protected $connection = "mysql";

    protected $guarded = [];

    protected $sortable = [
        // 设置排序字段名称
        'order_column_name' => 'order',
        // 是否在创建时自动排序，此参数建议设置为true
        'sort_when_creating' => true,
    ];


    protected $casts = [
        'status' => 'boolean',
        'status_already' => 'boolean',
        'select_bank' => 'boolean',
        'title' => 'json',
        'config' => 'json',
        'remark' => 'json',
    ];

    /**
     * @return HasMany
     */
    public function channelList(): HasMany
    {
        return $this->hasMany(RechargeChannelList::class)->orderByDesc('order');
    }

    /**
     * @return HasOne
     */
    public function channelListItem(): HasOne
    {
        return $this->hasOne(RechargeChannelList::class)->inRandomOrder();
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


    public function updateMinAdnMaxMoney()
    {
        if ($this->channelList()->count() > 0) {
            $min = $this->channelList()->min('min_money');
            $max = $this->channelList()->min('max_money');
            if ($min > 0) $this->min_money = $min;
            if ($max > 0) $this->max_money = $max;
            if ($min + $max > 0) $this->save();
        }


    }

}
