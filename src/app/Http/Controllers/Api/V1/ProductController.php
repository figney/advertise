<?php


namespace App\Http\Controllers\Api\V1;


use App\Enums\ProductType;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ProductResource;
use App\Http\Resources\UserProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends ApiController
{

    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }


    /**
     * 投资产品列表-tzcplb
     * @group 产品-Product
     * @param Request $request
     */
    public function products(Request $request)
    {

        $user = $this->user();

        $orm = Product::query()->where('status', 1)->with(['allUserBuys'])->orderByDesc('order');

        $user_buy_count = 0;

        if ($user) {
            $orm->with('userBuys', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
            $user_buy_count = $user->products()->count();
        }


        $list = $orm->get()->makeHidden(['content', 'big_cover', 'select_money_list']);

        $list = collect($list)->map(function (Product $product) use ($user_buy_count) {
            $product->can_buy = true;
            if ($product->is_no_buy_user && $user_buy_count > 0) $product->can_buy = false;
            if ($product->user_max_buy > 0 && $product->userBuys->count() >= $product->user_max_buy) $product->can_buy = false;
            return $product;
        })->all();

        /*$list = collect($list)->filter(function (Product $product) use ($user_buy_count) {

            if ($product->is_no_buy_user && $user_buy_count > 0) return false;
            if ($product->user_max_buy > 0 && $product->userBuys->count() >= $product->user_max_buy) return false;
            return true;
        })->all();*/

        return $this->response([
            'list' => ProductResource::collection($list),
        ]);

    }

    /**
     * 投资产品详情-tzcpxq
     * @group 产品-Product
     * @queryParam id int  required 产品ID
     * @param Request $request
     */
    public function product(Request $request)
    {
        try {

            $this->validatorData($request->all(), [
                'id' => 'required'
            ]);
            $user = $this->user();

            $id = $request->input('id');

            $orm = Product::query()->where('status', 1)->with(['allUserBuys']);

            $user_buy_count = 0;

            if ($user) {
                $orm->with('userBuys', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
                $user_buy_count = $user->products()->count();
            }
            $product = $orm->find($id);

            $product->can_buy = true;
            if ($product->is_no_buy_user && $user_buy_count > 0) $product->can_buy = false;
            if ($product->user_max_buy > 0 && $product->userBuys->count() >= $product->user_max_buy) $product->can_buy = false;

            $res['product'] = ProductResource::make($product);

            return $this->response($res);

        } catch (\Exception $exception) {
            return $this->responseError($exception->getMessage());
        }

    }

    /**
     * 购买投资产品-gmtzcp
     * @group 产品-Product
     * @queryParam id int  required 产品ID
     * @queryParam amount number  required 投资金额
     * @authenticated
     * @group 产品-Product
     */
    public function buyProduct(Request $request)
    {

        $user = $this->user();

        $lock = \Cache::lock('buyProduct:' . $user->id, 10);

        try {
            $this->validatorData($request->all(), [
                'id' => ['required'],
                'amount' => ['required', 'numeric'],
            ]);
            $lock->block(10);
            $id = $request->input('id');
            $amount = $request->input('amount');
            $product = $this->productService->getItem($id);
            $this->productService->buyProduct($user, $product, $amount);
            return $this->responseMessage(Lang('SUCCESS'));
        } catch (\Exception $exception) {
            return $this->responseException($exception);
        } finally {
            optional($lock)->release();
        }

    }

    /**
     * 用户投资产品列表-yhtzcplb
     * @queryParam page int  分页
     * @queryParam page_size int  每页大小
     * @queryParam is_over bool  是否已结算
     * @queryParam product_type string  类型
     * @authenticated
     * @group 产品-Product
     */
    public function userProducts(Request $request)
    {
        try {


            $is_over = $request->boolean('is_over');
            $page = (int)$request->input('page', 1);
            $page_size = (int)$request->input('page_size', 20);
            $product_type = $request->input('product_type');

            $user = $this->user();

            $obj = $user->products()->with(['product'])->orderByDesc('id');

            if ($is_over) {
                $obj->where('is_over', 1);
            }

            if ($product_type) {

                abort_if(!in_array($product_type, ProductType::asArray()), 400, Lang('类型错误'));

                $obj->where('product_type', $product_type);
            }

            $list = $obj->paginate($page_size);


            $res['list'] = UserProductResource::collection($list);

            if ($page === 1) $res['all_property'] = $this->productService->getUserProductData($user);

            return $this->response($res);


        } catch (\Exception $exception) {
            return $this->responseError($exception->getMessage());
        }
    }

}
