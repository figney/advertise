<?php


namespace App\Http\Controllers\Api\V1;


use App\Enums\NextActionType;
use App\Enums\PlatformType;
use App\Enums\RechargeChannelType;
use App\Enums\TransferVoucherCheckType;
use App\Enums\WalletType;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\RechargeChannelResource;
use App\Http\Resources\UserRechargeOrderResource;
use App\Http\Resources\UserTransferVoucherResource;
use App\Models\CoinAddress;
use App\Models\RechargeChannel;
use App\Models\RechargeChannelList;
use App\Models\UserTransferVoucher;
use App\Services\OnlinePayService;
use App\Services\Pay\FPayTHBService;
use App\Services\Pay\IPayIndianService;
use App\Services\Pay\JstPayService;
use App\Services\Pay\YudrsuService;
use App\Services\RechargeService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RechargeController extends ApiController
{

    public function __construct(protected RechargeService $rechargeService, protected OnlinePayService $onlinePayService)
    {
    }


    /**
     * 充值界面信息初始化-czjmxxcsh
     * @group 充值-recharge
     * @return JsonResponse
     */
    public function begin()
    {
        try {
            $user = $this->user();

            $select_list = Setting('first_recharge_select');
            $select_list = collect($select_list)->map(function ($item) {
                return (float)$item;
            })->all();

            $res['select_list'] = $select_list;

            //支付渠道
            $res['channel'] = RechargeChannelResource::collection($this->rechargeService->getChannel($user));
            //$res['channel'] = $this->rechargeService->getChannel($user);


            return $this->response($res);

        } catch (\Exception $exception) {
            return $this->responseException($exception);
        }
    }

    /**
     * 获取充值订单信息-hqczddxx
     * @queryParam order_sn   required 订单编号
     * @group 充值-recharge
     * @authenticated
     * @return JsonResponse
     */
    public function getUserRechargeOrder(Request $request)
    {
        try {

            $this->validatorData($request->all(), [
                'order_sn' => 'required'
            ]);
            $order_sn = $request->input('order_sn');
            $user = $this->user();

            $order = $user->rechargeOrders()->where('order_sn', $order_sn)->first();
            abort_if(!$order, 400, '参数错误');

            $res = UserRechargeOrderResource::make($order);

            return $this->response($res);


        } catch (\Exception $exception) {
            return $this->responseException($exception);
        }

    }


    /**
     * 提交在线支付-tjzxzf
     * @queryParam channel_id int  required 充值渠道ID
     * @queryParam channel_item_id int  required 充值渠道列表选项ID，没有传0
     * @queryParam son_code string   充值渠道列表选项子银行code
     * @queryParam amount number  required 充值金额
     * @queryParam next_action   required 充值目标 Wallet Money Product
     * @queryParam next_id int   required 充值目标id  产品用，其他传0
     * @queryParam next_data object   充值目标附加数据  ，对象数据类型
     * @queryParam redirect_url    required 跳转地址
     * @group 充值-recharge
     * @authenticated
     * @param Request $request
     */
    public function putInInOnlineOrder(Request $request)
    {
        $user = $this->user();
        $order = null;
        try {
            $data = $request->all();
            $this->validatorData($data, [
                'channel_id' => 'required',
                'channel_item_id' => 'required',
                'amount' => 'required|numeric',
                'next_action' => ['required', Rule::in(NextActionType::asArray())],
                'next_id' => 'required|numeric',
                'redirect_url' => 'required',
                'next_data' => 'array',
            ]);


            $channel_id = data_get($data, 'channel_id');
            $channel_item_id = data_get($data, 'channel_item_id');
            $son_code = data_get($data, 'son_code');
            $amount = data_get($data, 'amount');
            $next_action = data_get($data, 'next_action');
            $next_id = data_get($data, 'next_id');
            $next_data = data_get($data, 'next_data');
            $redirect_url = data_get($data, 'redirect_url');

            $rechargeChannel = RechargeChannel::query()->where('type', RechargeChannelType::OnLine)->find($channel_id);

            abort_if(!$rechargeChannel, 400, Lang('参数错误'));


            $rechargeChannelList = null;
            if ($channel_item_id > 0) {
                $rechargeChannelList = RechargeChannelList::query()->find($channel_item_id);
            }

            //检测金额
            $this->rechargeService->checkAmount($amount, $rechargeChannel, $rechargeChannelList);

            //检测时间
            switch ($rechargeChannel->slug) {
                case PlatformType::Yudrsu:
                    if (Carbon::now()->between(Carbon::make("22:50"), Carbon::make("23:10"))) {
                        return $this->responseError(Lang('充值维护'));
                    }
                    break;
            }


            $order = $this->rechargeService->createRechargeOrder($user, WalletType::balance, $amount, $next_action, $next_id, $rechargeChannel, $rechargeChannelList);


            $order->next_data = $next_data;
            $order->save();

            //设置跳转地址
            $redirect_url = str_replace("ORDER_SN", $order->order_sn, $redirect_url);


            if (!\App::isProduction()) {
                $res['pay_url'] = $redirect_url;
                $res['order_sn'] = $order->order_sn;
                return $this->response($res);
            }

            switch ($rechargeChannel->slug) {
                case PlatformType::PayTM:
                    $pay_url = $this->onlinePayService->paytmCashPayIn($user, $order, $rechargeChannel, $redirect_url);
                    if ($pay_url) {
                        $res['pay_url'] = $pay_url;
                        $res['order_sn'] = $order->order_sn;
                        return $this->response($res);
                    }
                    break;
                case PlatformType::IPayIndian:
                    $pay_url = IPayIndianService::make()->payIn($user, $order, $rechargeChannel, $redirect_url);
                    if ($pay_url) {
                        $res['pay_url'] = $pay_url;
                        $res['order_sn'] = $order->order_sn;
                        return $this->response($res);
                    }
                case PlatformType::FPay:
                    $pay_url = FPayTHBService::make()->withConfig($rechargeChannel)->payIn($user, $order, $rechargeChannelList, $redirect_url);
                    if ($pay_url) {
                        $res['pay_url'] = $pay_url;
                        $res['order_sn'] = $order->order_sn;
                        return $this->response($res);
                    }
                case PlatformType::Yudrsu:
                    $pay_url = YudrsuService::make()->withConfig($rechargeChannel)->payIn($user, $order, $rechargeChannelList, $redirect_url, $son_code);
                    if ($pay_url) {
                        $res['pay_url'] = $pay_url;
                        $res['order_sn'] = $order->order_sn;
                        return $this->response($res);
                    }
                case PlatformType::JstPay:
                    $pay_url = JstPayService::make()->payIn($user, $order, $rechargeChannelList, $redirect_url);
                    if ($pay_url) {
                        $res['pay_url'] = $pay_url;
                        $res['order_sn'] = $order->order_sn;
                        return $this->response($res);
                    }
            }


            abort(400, Lang('ERROR'));


        } catch (\Exception $exception) {
            \Log::error("创建在线订单失败:" . $exception->getMessage(), ['user_id' => $user->id]);
            return $this->responseException($exception);
        }

    }


    /**
     * 获取USDT充值地址-hqudz
     * @queryParam channel_id int  required 充值渠道ID
     * @group 充值-recharge
     * @authenticated
     * @param Request $request
     * @return JsonResponse
     */
    public function getUsdtAddress(Request $request): JsonResponse
    {

        $lock = \Cache::lock('getUsdtAddress', 10);

        try {
            $this->validatorData($request->all(), [
                'channel_id' => 'required'
            ]);
            $lock->block(10);

            $channel_id = $request->input('channel_id');
            $user = $this->user();
            $rc = RechargeChannel::query()->where('id', $channel_id)->where('type', RechargeChannelType::USDT_TRC20)->where('status', 1)->firstOrFail();
            $ca = CoinAddress::query()->where('recharge_channel_id', $rc->id)->where('user_id', $user->id)->first();
            if ($ca) {
                $lock->release();
                return $this->response([
                    'address' => $ca->address
                ]);
            } else {
                $ca = CoinAddress::query()->where('recharge_channel_id', $rc->id)->where('user_id', 0)->first();
                abort_if(!$ca, 400, Lang('ERROR'));
                $ca->user_id = $user->id;
                $ca->allocation_time = now();
                $ca->save();
                $lock->release();
                return $this->response([
                    'address' => $ca->address
                ]);
            }
        } catch (\Exception $exception) {
            return $this->responseException($exception);
        } finally {
            optional($lock)->release();
        }

    }

    /**
     * 人工转账记录-rgzztjjl
     * @group 充值-recharge
     * @authenticated
     */
    public function transferVoucherList()
    {
        try {
            $user = $this->user();

            $userTransferVoucher = $user->transferVoucher()->with(['channelItem']);


            $list = $userTransferVoucher->get();

            $res['list'] = UserTransferVoucherResource::collection($list);

            return $this->response($res);

        } catch (\Exception $exception) {
            return $this->responseException($exception);
        }
    }

    /**
     * 提交人工转账订单-tjzzpz
     * @queryParam channel_item_id int  required 充值渠道收款信息ID
     * @queryParam user_name  required 名称
     * @queryParam card_number  required 卡号
     * @queryParam bank_name  required 银行名称
     * @queryParam amount  required 金额
     * @queryParam time  required 时间
     * @queryParam image file  required 图片
     * @queryParam next_action   required 充值目标 Wallet Money Product
     * @queryParam next_id int   required 充值目标id  产品用，其他传0
     * @queryParam next_data object
     * @group 充值-recharge
     * @authenticated
     */
    public function putInTransferVoucher(Request $request)
    {
        try {
            $user = $this->user();


            $data = $request->all();
            $this->validatorData($data, [
                'channel_item_id' => 'required',
                'user_name' => 'required',
                'card_number' => 'required',
                'bank_name' => 'required',
                'amount' => 'required|numeric',
                'time' => 'required|date',
                'image' => 'required|file|image|max:5120|mimes:jpeg,jpg,png,gif',
                'next_action' => ['required', Rule::in(NextActionType::asArray())],
                'next_id' => 'required|numeric',
                'next_data' => 'json',
            ]);


            $imageFile = $request->file('image');
            $image_md5 = md5_file($imageFile->path());


            abort_if(UserTransferVoucher::query()->where('image_md5', $image_md5)->lockForUpdate()->count() > 0, 400, Lang('凭证图片重复提交'));

            $channel_item_id = data_get($data, 'channel_item_id');

            $channel_item = RechargeChannelList::query()->where('id', $channel_item_id)->first();

            abort_if(!$channel_item, 400, Lang('收款方式错误'));

            $user_name = data_get($data, 'user_name');
            $card_number = data_get($data, 'card_number');
            $bank_name = data_get($data, 'bank_name');
            $amount = data_get($data, 'amount');
            $time = data_get($data, 'time');
            $next_action = data_get($data, 'next_action');
            $next_id = data_get($data, 'next_id');
            $next_data = data_get($data, 'next_data');

            $check_arr = [$channel_item->card_user_name, $channel_item->card_number];
            abort_if(in_array($channel_item, $check_arr) || in_array($card_number, $check_arr), 400, Lang('输入错误'));
            abort_if($amount < $channel_item->min_money, 400, Lang('输入金额不能低于', [(float)$channel_item->min_money]));

            //检测金额
            $this->rechargeService->checkAmount($amount, $channel_item->rechargeChannel, $channel_item);

            \DB::beginTransaction();

            $order = $this->rechargeService->createRechargeOrder($user, WalletType::balance, $amount, $next_action, $next_id, $channel_item->rechargeChannel, $channel_item);

            $order->next_data = $next_data;
            $order->save();

            $imagePath = $imageFile->store('voucher');

            $utv = UserTransferVoucher::query()->create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'user_level' => $user->invite->level,
                'channel_id' => $user->channel_id,
                'link_id' => $user->link_id,
                'image' => $imagePath,
                'image_md5' => $image_md5,
                'channel_item_id' => $channel_item_id,
                'user_name' => $user_name,
                'card_number' => $card_number,
                'bank_name' => $bank_name,
                'amount' => $amount,
                'time' => $time,
                'status' => false,
                'check_type' => TransferVoucherCheckType::UnderReview,
                'next_action' => $next_action,
                'next_id' => $next_id,
            ]);

            $order->platform_sn = $utv->id;
            $order->actual_amount = $amount;
            $order->save();

            \DB::commit();
            return $this->responseMessage(Lang('success'));


        } catch (\Exception $exception) {
            return $this->responseException($exception);
        }
    }


    /**
     * 充值记录-czjl
     * @queryParam page int  分页
     * @queryParam page_size int  每页大小
     * @group 充值-recharge
     * @authenticated
     * @param Request $request
     */
    public function userRechargeOrderList(Request $request)
    {

        $user = $this->user();

        $page_size = (int)$request->input('page_size', 15);

        $orm = $user->rechargeOrders()->orderByDesc('id');

        $list = $orm->paginate($page_size);

        $res['list'] = UserRechargeOrderResource::collection($list);

        return $this->response($res);

    }


}
