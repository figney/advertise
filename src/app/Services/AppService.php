<?php


namespace App\Services;


use App\Enums\HeaderType;
use App\Models\Setting;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

class AppService extends BaseService
{


    /**
     * 获取客户端语言
     * @return string
     */
    public function lang(): string
    {
        $b_lang = (new Agent())->languages();

        $i_lang = request()->header(HeaderType::Lang, data_get($b_lang, 0));


        if (empty($i_lang)) {
            $i_lang = Setting('default_lang');
        }

        return $i_lang;

    }


    /**
     * 获取语言标识
     * @return string
     */
    public function local($local = null, $isObj = false)
    {


        $default_lang = Setting('default_lang');

        $langList = LangService::make()->getLangList();


        if ($local == null) {
            $lang = Str::upper($this->lang());
            $local = $lang;
            if (Str::contains($lang, "ZH")) {
                $local = "CN";
            }
            if (Str::contains($lang, "CN")) {
                $local = "CN";
            }
            if (Str::contains($lang, "EN")) {
                $local = "EN";
            }
            if (Str::contains($lang, "ID")) {
                $local = "ID";
            }

        }

        $langList = collect($langList)->filter(function ($item) use ($local) {
            return $item->slug == $local;
        });

        if ($langList->count() <= 0) {
            $local = $default_lang;
        }


        return $isObj ? $langList->first() : $local;
    }


    /**
     * 获取配置信息对象
     * @param int $channel_id
     * @return Setting|\GeneaLabs\LaravelModelCaching\CachedBuilder|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
     */
    public function getSettings($channel_id = 1)
    {
        return Setting::query()->where('channel_id', $channel_id)->firstOrFail();

    }

    /**
     * 获取系统在线人数
     * @return mixed
     */
    public function getOnlineNumber()
    {
        $get_url = Setting('socket_url');

        $res = \Http::get($get_url)->collect();

        return $res->get('allUserNum', 0);
    }

}
