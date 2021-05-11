<?php


namespace App\Services;


use App\Models\Domain;
use App\Models\Share;

class ShareService extends BaseService
{

    /**
     * 根据语言获取分享内容列表
     * @param $local
     * @return array
     */
    public function getShare($local)
    {

        return \Cache::tags(['SHARE'])->rememberForever("SHARE-" . $local, function () use ($local) {
            return Share::query()->where('status', 1)->get()->map(function (Share $item) use ($local) {
                $item->default_cover = ImageUrl($item->default_cover);
                $item->title = data_get($item->title, $local);
                $item->describe = data_get($item->describe, $local);
                $cover = data_get($item->cover, $local);
                $item->cover = $cover ?? $item->default_cover;
                return [
                    'title' => $item->title,
                    //'describe' => $item->describe,
                    //'cover' => $item->cover,
                ];
            })->toArray();
        });


    }


    public function getDomain($type = "share")
    {

        $domain = Domain::whereType($type)->inRandomOrder()->first();

        return $domain;

    }

}
