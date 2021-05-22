<?php


namespace App\Services;


use App\Enums\LanguageConfigType;
use App\Models\Language;
use App\Models\LanguageConfig;
use Illuminate\Support\Str;

class LangService extends BaseService
{

    public function getLangList()
    {
        return Language::query()->where('status', true)->orderByDesc('order')->get(['name', 'slug', 'icon', 'value']);
    }


    public function getLang($local, $slug)
    {
        $allLang = $this->allLang(LanguageConfigType::serve);

        $itemLang = data_get($allLang, $slug);

        return data_get($itemLang, $local);

    }

    public function createLang($slug, $params)
    {

        abort_if(Str::containsAll($slug, ["{", "}"]), 400, "语言标识错误");

        $langContent = $slug;

        if (count($params) > 0) {
            foreach ($params as $key => $value) {
                $langContent .= "---{" . $key . "}";
            }
        }

        foreach (Language::query()->get() as $lang) {
            $content[$lang->slug] = $langContent;
        }

        LanguageConfig::query()->firstOrCreate(['slug' => $slug], [
            'type' => LanguageConfigType::serve,
            'name' => $slug,
            'content' => $content,
            'group' => 'AutoGenerate'
        ]);
    }

    public function allLang($type)
    {
        return LanguageConfig::query()->where('type', $type)->pluck('content', 'slug');
    }

    public function getIn18n($group = null)
    {

        $orm = LanguageConfig::query()->where('type', LanguageConfigType::client);

        if ($group) {
            $orm->where('group', $group);
        }

        $list = $orm->pluck('content', 'slug');

        $local = $this->getLocal();

        $list = collect($list)->map(function ($item) use ($local) {
            return data_get($item, $local);
        })->all();

        return $list;
    }


}
