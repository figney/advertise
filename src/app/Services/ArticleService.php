<?php


namespace App\Services;


use App\Models\Article;

class ArticleService extends BaseService
{

    public function getList($type)
    {
        return Article::query()->where('status', true)->where('type', $type)->orderByDesc('order')->get()->map(function (Article $item) {
            $item = $this->setItem($item);
            return $item;
        })->all();
    }

    public function getListBySlug(array $slug)
    {
        return Article::query()->where('status', true)->whereIn('slug', $slug)->orderByDesc('order')->get()->map(function (Article $item) {
            $item = $this->setItem($item);
            return $item;
        })->all();
    }


    public function setItem(Article $item)
    {

        $local = $this->getLocal();

        $item->title = data_get($item->title, $local);
        $item->describe = data_get($item->describe, $local);
        $item->content = data_get($item->content, $local);
        return $item;
    }

}
