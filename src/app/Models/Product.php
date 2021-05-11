<?php


namespace App\Models;


use Dcat\Admin\Traits\HasDateTimeFormatter;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use  HasDateTimeFormatter;


    protected $casts = [
        'is_number_buy' => 'bool',
        'is_commission' => 'bool',
        'status' => 'bool',
        'content' => 'json',
        'describe' => 'json',
        'select_money_list' => 'json',
        'title' => 'json',
        'attrs' => 'json',
        'commission_config' => 'json',
    ];

    protected $guarded = [];

    public function userBuys()
    {
        return $this->hasMany(UserProduct::class);
    }

    public function allUserBuys()
    {
        return $this->hasMany(UserProduct::class);
    }


    public function getSelectMoneyList()
    {

        return collect($this->select_money_list)->filter(function ($item) {
            if ($this->is_number_buy) return $item >= 1;
            return $item >= $this->min_money;
        })->map(function ($item) {
            return (int)$item;
        })->all();
    }

    public function getAttrData()
    {
        return collect($this->attrs)->map(function ($item) {
            $i['value'] = data_get($item, 'value');
            $i['name'] = Lang(data_get($item, 'slug'));

            return $i;
        })->all();
    }

}
