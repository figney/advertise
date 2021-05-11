<?php


namespace App\Http\Controllers\Api\V1;


use App\Enums\WithdrawChannelType;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\UserWithdrawOrderResource;
use App\Http\Resources\WithdrawChannelResource;
use App\Models\UserAwardRecord;
use App\Models\WithdrawChannel;
use App\Services\WithdrawService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WithdrawController extends ApiController
{

    public function __construct(protected WithdrawService $withdrawService)
    {

    }


    /**
     * 提现信息初始化-txxxcsh
     * @group 提现-withdraw
     * @authenticated
     * @return JsonResponse
     */
    public function begin(): JsonResponse
    {
        try {
            $user = $this->user();
            //提现渠道
            $list = $this->withdrawService->getChannelList();

            $res['list'] = WithdrawChannelResource::collection($list);

            return $this->response($res);

        } catch (\Exception $exception) {
            return $this->responseException($exception);
        }
    }

    /**
     * 提现记录-txlj
     * @queryParam page int  分页
     * @queryParam page_size int  每页大小
     * @group 提现-withdraw
     * @authenticated
     * @param Request $request
     * @return JsonResponse
     */
    public function withdrawList()
    {
        try {
            $user = $this->user();

            $obj = $user->withdrawOrders()->orderBy('id', 'desc')->with(['withdrawChannel', 'withdrawChannelItem']);


            $list = $obj->paginate();

            $res['list'] = UserWithdrawOrderResource::collection($list);

            return $this->response($res);

        } catch (\Exception $exception) {
            return $this->responseException($exception);
        }

    }

    /**
     * 获取提现扣除明细-txkcmx
     * @queryParam type required 提现类型
     * @queryParam amount required 提现金额
     * @queryParam channel_id required 提现渠道ID
     * @group 提现-withdraw
     * @authenticated
     */
    public function getDeductInfo(Request $request)
    {
        $user = $this->user();

        $this->validatorData($request->all(), [
            'type' => 'required',
            'amount' => 'required',
            'channel_id' => 'required',
        ]);

        $channel_id = $request->input('channel_id');

        $w_channel = WithdrawChannel::query()->where('status', true)->find($channel_id);

        abort_if(!$w_channel, 400, Lang('提现通道关闭'));

        $amount = (float)$request->input('amount');

        if ($w_channel->type == WithdrawChannelType::USDT_TRC20) {
            $amount = $amount * (float)Setting('usdt_money_rate');
        }

        list($deduct_money, $deduct_items, $beyond_money) = $this->withdrawService->deductAward($user, $amount,0);

        $user_give_balance = $user->wallet->give_balance;


        $res['deduct_money'] = $deduct_money;//需要扣除的赠送金
        $res['give_balance'] = (float)$user_give_balance;//赠送金余额剩余
        $res['deduct_items'] = $deduct_items;//赠送金余额剩余
        $res['money_bao_deduct_give_balance'] = $user_give_balance > $deduct_money ? 0 : $deduct_money - $user_give_balance;//需要从赚钱宝里额外扣除

        return $this->response($res);


    }


    /**
     * 提交提现申请-tjtxsq
     * @queryParam type required 提现类型
     * @queryParam amount required 提现金额
     * @queryParam channel_id required 提现渠道ID
     * @queryParam channel_item_id 提现渠道选项ID，没有则提交 0
     * @queryParam input_data array required 提现收款信息
     * @group 提现-withdraw
     * @authenticated
     * @param Request $request
     * @return JsonResponse
     */
    public function putInWithdraw(Request $request): JsonResponse
    {
        $user = $this->user();
        $lock = \Cache::lock("putInWithdraw:" . $user->id, 10);
        try {
            $lock->block(10);

            $this->validatorData($request->all(), [
                'type' => ['required', Rule::in(WithdrawChannelType::getValues())],
                'amount' => 'required',
                'channel_id' => 'required',
                'input_data' => 'required|array',
            ], [], function ($v) {
                $v->sometimes('amount', 'required|integer', function ($input) {
                    return $input->type == WithdrawChannelType::OnLine;
                });
            });
            $amount = $request->input('amount');
            $channel_id = $request->input('channel_id');
            $channel_item_id = $request->input('channel_item_id');
            $input_data = $request->input('input_data');
            $type = $request->input('type');
            $this->withdrawService->withdrawOrder($user, $channel_id, $channel_item_id, $type, $input_data, $amount);
            $lock->release();
            return $this->responseMessage(Lang('SUCCESS'));
        } catch (\Exception $exception) {
            return $this->responseException($exception);
        } finally {
            optional($lock)->release();
        }
    }

}
