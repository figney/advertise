<?php


namespace App\Http\Controllers\Api\V1;


use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use App\Services\ArticleService;

class HomeController extends ApiController
{

    /**
     * 首页数据-sysj
     */
    public function home()
    {

        //首页幻灯片
        $banners = Banner::query()->where('status', 1)->orderBy('order','desc')->get();
        $res['banners'] = BannerResource::collection($banners);

        //首页图文内容
        $res['articles'] = ArticleResource::collection(ArticleService::make()->getListBySlug(['INDEX_ABOUT', 'INDEX_PATTERN', 'INDEX_PROMOTION']));


        return $this->response($res);

    }

}
