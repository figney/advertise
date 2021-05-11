<?php


namespace App\Services;


use App\Enums\NextActionType;
use App\Enums\OrderStatusType;
use App\Enums\RechargeChannelType;
use App\Enums\WalletLogSlug;
use App\Enums\WalletLogType;
use App\Models\Notifications\RechargeOrderSuccessNotification;
use App\Models\RechargeChannel;
use App\Models\RechargeChannelList;
use App\Models\User;
use App\Models\UserRechargeOrder;
use App\Models\Wallet;
use App\Models\WalletLog;
use Godruoyi\Snowflake\Snowflake;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

class RechargeService extends BaseService
{

    protected WalletService $walletService;

    public function __construct()
    {
        $this->walletService = new WalletService();
    }

    /**
     * 获取充值渠道
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getChannel($user)
    {
        $orm = RechargeChannel::query();
        if ($user && $user->hasRecharge()) {
            $orm->with(['channelList' => fn($q) => $q->where('status', true)])->where('status_already', true)->orderByDesc('order_already');

        } else {
            $orm->with(['channelList' => fn($q) => $q->where('status', true)])->where('status', true)->orderByDesc('order');

        }


        return $orm->get()->groupBy('type')
            ->map(function ($items) {

                if (count($items) == 1) {
                    return $items[0];
                }

                $temp = array();
                foreach ($items as $v) {
                    for ($i = 0; $i < $v->weight; $i++) {
                        $temp[] = $v->id;
                    }
                }
                $num = count($temp);
                $int = mt_rand(0, $num - 1);
                $id = $temp[$int];
                return collect($items)->filter(fn($item) => $item->id == $id)->first();
            })->flatten()->all();

    }

    public function checkAmount($amount, RechargeChannel $rechargeChannel, ?RechargeChannelList $rechargeChannelList)
    {
        //渠道充值金额限定
        $min_money = $rechargeChannel->min_money;
        $max_money = $rechargeChannel->max_money;

        if ($rechargeChannelList) {
            $i_min_money = $rechargeChannelList->min_money;
            $i_max_money = $rechargeChannelList->max_money;
            //渠道选项充值金额限制
            $min_money = $i_min_money > $min_money ? $i_min_money : $min_money;
            $max_money = $i_max_money > $max_money ? $i_max_money : $max_money;
        }
        //判断最低金额
        abort_if($amount < $min_money, 400, Lang('单笔最少需充值', [(float)$min_money]));
        abort_if($max_money > 0 && $amount > $max_money, 400, Lang('单笔最多充值', [(float)$max_money]));

    }

    /**
     * 创建充值订单
     * @param User|Builder $user
     * @param $wallet_type
     * @param $amount
     * @param RechargeChannel $rechargeChannel
     * @return \Illuminate\Database\Eloquent\Builder|Model|UserRechargeOrder
     */
    public function createRechargeOrder($user, $wallet_type, $amount, $next_action, $next_id, RechargeChannel $rechargeChannel, ?RechargeChannelList $rechargeChannelList)
    {

        //检测类型
        abort_if(!$this->walletService->checkWalletType($wallet_type), 400, Lang('类型错误'));
        //检测连带操作类型
        abort_if(!in_array($next_action, NextActionType::asArray()), 400, Lang('参数错误'));

        $snowflake = new Snowflake;

        $order_sn = $snowflake->id();

        $son_code = request('son_code');

        return UserRechargeOrder::query()->create([
            'order_sn' => $order_sn,
            'user_id' => $user->id,
            'user_level' => $user->invite->level,
            'channel_id' => $user->channel_id,
            'link_id' => $user->link_id,
            'wallet_type' => $wallet_type,
            'recharge_type' => $rechargeChannel->type,
            'sub_amount' => $amount,
            'amount' => $amount,
            'next_action' => $next_action,
            'next_id' => $next_id,
            'recharge_channel_id' => $rechargeChannel->id,
            'recharge_channel_item_id' => $rechargeChannelList?->id ?? 0,
            'son_code' => $son_code,
            'order_status' => OrderStatusType::Paying,
            'local' => $user->local,
            'lang' => $user->lang,
        ]);

    }


    /**
     * 订单支付失败
     * @param UserRechargeOrder $userRechargeOrder
     * @param $remark
     */
    public function rechargeOrderError(UserRechargeOrder $userRechargeOrder)
    {
        $userRechargeOrder->order_status = OrderStatusType::PayError;
        $userRechargeOrder->save();
        //TODO 订单支付失败通知
    }

    /**
     * 订单关闭
     * @param UserRechargeOrder $userRechargeOrder
     */
    public function rechargeOrderClose(UserRechargeOrder $userRechargeOrder)
    {
        $userRechargeOrder->order_status = OrderStatusType::Close;
        $userRechargeOrder->save();
    }


    /**
     * 订单支付成功
     * @param UserRechargeOrder $userRechargeOrder
     * @param $action_type
     * @param null $closure
     */
    public function rechargeOrderSuccess(UserRechargeOrder $userRechargeOrder, $action_type, $closure = null)
    {

        $user = $userRechargeOrder->user;

        $lock = \Cache::lock("rechargeOrderSuccess:" . $userRechargeOrder->id, 100);

        try {
            $lock->block(10);

            $uro = UserRechargeOrder::query()->find($userRechargeOrder->id);

            abort_if($uro->order_status == OrderStatusType::PaySuccess, 400, "ORDER STATUS ERROR");

            $this->rechargeSuccess($user, $userRechargeOrder->wallet_type, $userRechargeOrder->amount, $action_type, $userRechargeOrder->recharge_type, $userRechargeOrder::class, $userRechargeOrder->id, function (Wallet $wallet, WalletLog $walletLog) use ($closure, $userRechargeOrder) {
                $userRechargeOrder->is_pay = true;
                $userRechargeOrder->pay_time = now();
                $userRechargeOrder->order_status = OrderStatusType::PaySuccess;
                $userRechargeOrder->wallet_log_id = $walletLog->id;
                $userRechargeOrder->save();
                //执行业务逻辑闭包
                if ($closure instanceof \Closure) {
                    call_user_func($closure, $wallet, $walletLog);
                }

            });
            //触发充值成功钩子
            UserHookService::make()->rechargeHook($user, $userRechargeOrder);

        } catch (\Exception $exception) {
            abort(400, "LOCK");
        } finally {
            optional($lock)->release();
        }


    }


    /**
     * 充值成功统一处理
     * @param User|Builder $user
     * @param string $wallet_type
     * @param float $fee
     * @param $action_type
     * @param $recharge_type
     * @param $target_type
     * @param $target_id
     * @param null $closure
     */
    private function rechargeSuccess(User $user, string $wallet_type, float $fee, $action_type, $recharge_type, $target_type, $target_id, $closure = null)
    {

        WalletService::make()->deposit($user, $fee, $wallet_type, WalletLogSlug::recharge, $action_type, $target_type, $target_id, function (Wallet $wallet, WalletLog $walletLog) use ($target_id, $target_type, $recharge_type, $fee, $wallet_type, $user, $closure) {
            //执行业务逻辑闭包
            if ($closure instanceof \Closure) {
                call_user_func($closure, $wallet, $walletLog);
            }
        });


    }

}


