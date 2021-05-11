<?php


namespace App\Http\Controllers\Api\V1;


use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\WalletLogResource;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WalletController extends ApiController
{
    protected WalletService $walletService;


    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }


    /**
     * 钱包余额转换-qbyezh
     *
     * @queryParam fee number required 转换数量
     * @queryParam to_balance boolean required 转换类型：true 为USDT转余额，false为余额转USDT
     * @authenticated
     * @param Request $request
     * @group 钱包-wallet
     * @return \Illuminate\Http\JsonResponse
     */
    public function transform(Request $request)
    {
        try {
            abort(400, Lang('ERROR'));

            $this->validatorData($request->all(), [
                'fee' => 'required|numeric',
                'to_balance' => 'required|boolean'
            ]);
            $user = $this->user();
            $fee = $request->input('fee');
            $to_balance = $request->boolean('to_balance');
            $this->walletService->transform($user, $fee, $to_balance);
            return $this->responseMessage(Lang("SUCCESS"));
        } catch (\Exception $exception) {
            return $this->responseError($exception->getMessage());
        }

    }

    /**
     * 钱包流水-qbls
     *
     * @queryParam page int  分页
     * @queryParam page_size int  每页大小
     * @queryParam wallet_slug 标识记录
     * @queryParam wallet_type 钱包类型
     * @queryParam action_type 操作类型,多个用 英文 ","隔开
     * @queryParam date date 查询日期
     * @authenticated
     * @param Request $request
     * @group 钱包-wallet
     * @return \Illuminate\Http\JsonResponse
     */
    public function walletLogs(Request $request)
    {
        try {


            $user = $this->user();

            $page_size = (int)$request->input('page_size', 15);

            $obj = $user->WalletLogMongo()->orderByDesc('created_at');

            $wallet_slug = $request->input('wallet_slug');
            if ($wallet_slug) {
                $obj->where('wallet_slug', $wallet_slug);
            }

            $wallet_type = $request->input('wallet_type');
            if ($wallet_type) {
                $obj->where('wallet_type', $wallet_type);
            }

            $action_type = $request->input('action_type');
            if ($action_type) {
                $obj->whereIn('action_type', explode(",", $action_type));
            }

            $date = $request->input('date');
            if ($date) {
                $obj->whereBetween('created_at', [Carbon::make($date)->startOfDay(), Carbon::make($date)->endOfDay()]);
            }


            $list = $obj->paginate($page_size);

            $res['list'] = WalletLogResource::collection($list);

            return $this->response($res);


        } catch (\Exception $exception) {
            return $this->responseError($exception->getMessage());
        }
    }

}
