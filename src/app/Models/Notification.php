<?php


namespace App\Models;


use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Support\Str;
use Jenssegers\Mongodb\Eloquent\Model;


class Notification extends Model
{
    use HasDateTimeFormatter;

    protected $connection = "mongodb";
    protected $guarded = [];

    protected $dates = ['read_time'];


    public function read()
    {
        return $this->is_read;
    }


    public function title()
    {
        $local = data_get($this, 'local');

        return Lang($this->title_slug, [], $local);
    }

    public function content()
    {
        $params = $this->params;

        $local = data_get($this, 'local');

        $params = collect($params)->map(function ($value, $key) use ($local) {


            if (Str::contains($key, ['_lang', '_type'])) {
                return Lang(Str::upper($value), [], $local);
            }

            if (Str::contains($key, ['fee', 'amount', 'money'])) {
                return round($value, 4);
            }

            return $value;

        })->values()->toArray();


        return Lang($this->content_slug, $params, $local);
    }

    public function getData()
    {
        return collect($this->data)->values();
    }


    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }


}
