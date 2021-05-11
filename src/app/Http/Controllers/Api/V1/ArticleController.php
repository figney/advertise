<?php


namespace App\Http\Controllers\Api\V1;


use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Services\ArticleService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArticleController extends ApiController
{

    /**
     * 文章列表-wzlb
     * @queryParam type required 类型 help=帮助中心
     * @param Request $request
     */
    public function getList(Request $request)
    {

        try {
            $this->validatorData($request->all(), [
                "type" => [Rule::in(Article::TYPE)],
            ]);
            $list = ArticleService::make()->getList($request->input('type'));
            return $this->response([
                'list' => $list,
            ]);

        } catch (\Exception $exception) {
            return $this->responseError($exception->getMessage());
        }
    }

    /**
     * 获取文章-hqdtwz
     * @queryParam slug required 标识数组  ['INVITE_RULE']
     * @param Request $request
     */
    public function getArticle(Request $request)
    {
        try {
            $this->validatorData($request->all(), [
                "slug" => 'required',
            ]);

            $slug = $request->input('slug');

            $is_list = true;

            if (!is_array($slug)) {
                $slug = [$slug];
                $is_list = false;
            }

            $list = ArticleService::make()->getListBySlug($slug);


            if (!$is_list) {
                $item = collect($list)->first();
                $res['articles'] = $item ? ArticleResource::make($item) : null;
            } else {
                $res['articles'] = ArticleResource::collection($list);
            }

            return $this->response($res);
        } catch (\Exception $exception) {
            return $this->responseException($exception);
        }
    }

}
