<?php


namespace App\Http\Controllers\Api\V1;


use App\Enums\WalletType;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\UserMoneyBaoResource;
use App\Services\MoneyBaoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MoneyBaoController extends ApiController
{

    /**
     * 余额存入赚钱宝-yecrzqb
     * @queryParam fee number required 存入数量
     * @queryParam wallet_type string required 余额类型  balance，usdt，give
     * @authenticated
     * @param Request $request
     * @group 钱包-wallet
     * @return \Illuminate\Http\JsonResponse
     */
    public function depositMoneyBao(Request $request)
    {
        $user = $this->user();
        $lock = \Cache::lock("depositMoneyBao:" . $user->id, 10);

        try {
            $lock->block(10);
            $this->validatorData($request->all(), [
                'fee' => 'required|numeric',
                'wallet_type' => ['required', Rule::in(WalletType::asArray())],
            ]);
            $fee = $request->input('fee');
            $wallet_type = $request->input('wallet_type');

            MoneyBaoService::make()->depositMoneyBao($user, $fee, $wallet_type);

            $money_bao = $user->moneyBao()->first();
            return $this->response([
                'money_bao' => UserMoneyBaoResource::make($money_bao)
            ]);

        } catch (\Exception $exception) {
            return $this->responseError($exception->getMessage());
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * 赚钱宝转出到余额-yecrzqb
     * @queryParam fee number required 转出数量
     * @queryParam wallet_type string required 余额类型  balance，usdt，give
     * @authenticated
     * @param Request $request
     * @group 钱包-wallet
     * @return \Illuminate\Http\JsonResponse
     */
    public function takeOutMoneyBao(Request $request)
    {
        $user = $this->user();
        $lock = \Cache::lock("takeOutMoneyBao:" . $user->id, 10);
        try {
            $lock->block(10);
            $this->validatorData($request->all(), [
                'fee' => 'required|numeric',
                'wallet_type' => ['required', Rule::in(WalletType::asArray())],
            ]);
            $fee = $request->input('fee');
            $wallet_type = $request->input('wallet_type');
            MoneyBaoService::make()->takeOutMoneyBao($user, $fee, $wallet_type);
            $money_bao = $user->moneyBao()->first();
            return $this->response([
                'money_bao' => UserMoneyBaoResource::make($money_bao)
            ]);
        } catch (\Exception $exception) {
            return $this->responseError($exception->getMessage());
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * 领取赚钱宝收益-lqzqbsy
     * @queryParam wallet_type string required 余额类型  balance，usdt，give
     * @group 钱包-wallet
     * @param Request $request
     */
    public function receiveMoneyBaoAward(Request $request)
    {
        $user = $this->user();
        $lock = \Cache::lock("receiveMoneyBaoAward:" . $user->id, 10);
        try {
            $lock->block(10);
            $this->validatorData($request->all(), [
                'wallet_type' => ['required', Rule::in(WalletType::asArray())],
            ]);
            $wallet_type = $request->input('wallet_type');
            MoneyBaoService::make()->receiveMoneyBaoAward($user, $wallet_type);
            $money_bao = $user->moneyBao()->first();
            return $this->response([
                'money_bao' => UserMoneyBaoResource::make($money_bao)
            ]);
        } catch (\Exception $exception) {
            return $this->responseError($exception->getMessage());
        } finally {
            optional($lock)->release();
        }
    }

}
