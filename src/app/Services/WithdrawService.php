<?php


namespace App\Services;


use App\Enums\OrderStatusType;
use App\Enums\PlatformType;
use App\Enums\WalletLogSlug;
use App\Enums\WalletLogType;
use App\Enums\WalletType;
use App\Enums\WithdrawChannelType;
use App\Enums\WithdrawOrderStatusType;
use App\Models\Notifications\UserDeductAwardNotification;
use App\Models\Notifications\UserFriendDeductAwardNotification;
use App\Models\Notifications\UserWithdrawRefundNotification;
use App\Models\Notifications\UserWithdrawRejectNotification;
use App\Models\Notifications\UserWithdrawToPayErrorNotification;
use App\Models\Notifications\UserWithdrawToPayNotification;
use App\Models\Notifications\UserWithdrawToPaySuccessNotification;
use App\Models\User;
use App\Models\UserAwardRecord;
use App\Models\UserWithdrawOrder;
use App\Models\Wallet;
use App\Models\WalletLog;
use App\Models\WithdrawChannel;
use App\Models\WithdrawChannelList;
use App\Services\Pay\BananaPayService;
use App\Services\Pay\FPayTHBService;
use App\Services\Pay\IPayIndianService;
use App\Services\Pay\IvnPayService;
use App\Services\Pay\JstPayService;
use App\Services\Pay\PayPlusService;
use App\Services\Pay\PaytmCashService;
use App\Services\Pay\YudrsuService;
use Godruoyi\Snowflake\Snowflake;
use Illuminate\Database\Query\Builder;

class WithdrawService extends BaseService
{

    /**
     * 获取提现列表
     * @return WithdrawChannel[]|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection
     */
    public function getChannelList()
    {
        return WithdrawChannel::query()->where('status', true)->with(['channelList' => fn($query) => $query->where('status', true)])->orderByDesc('order')->get();
    }


    /**
     * 提现处理
     * @param $user
     * @param $channel_id
     * @param $channel_item_id
     * @param $type
     * @param $input_data
     */
    public function withdrawOrder($user, $channel_id, $channel_item_id, $type, $input_data, float $amount)
    {
        $w_channel = WithdrawChannel::query()->where('status', true)->find($channel_id);

        //渠道是否存在
        abort_if(!$w_channel, 400, "error 1");
        //提现类型
        abort_if($w_channel->type !== $type, 400, "error 2");
        //是否必须选择选项
        $w_channel_item = null;
        if ($w_channel->select_bank) {
            abort_if($channel_item_id <= 0, 400, "error 3");
            $w_channel_item = WithdrawChannelList::query()->where('withdraw_channel_id', $channel_id)->where('status', true)->find($channel_item_id);
            //渠道选项是否是否存在
            abort_if(!$w_channel_item, 400, "error 4");
        }
        //提现输入信息
        $data = $this->checkInputData($w_channel, $w_channel_item, $input_data);
        //检测提现金额
        $this->checkAmount($amount, $w_channel, $w_channel_item);
        //创建提现信息
        $this->createWithdrawOrder($user, $w_channel, $w_channel_item, $data, $amount);
    }


    /**
     * 提现订单拒绝
     * @param UserWithdrawOrder $userWithdrawOrder
     */
    public function rejectWithdrawOrder(UserWithdrawOrder $userWithdrawOrder)
    {
        $userWithdrawOrder->order_status = WithdrawOrderStatusType::CheckError;
        $userWithdrawOrder->back_time = now();
        $userWithdrawOrder->save();

        $user = $userWithdrawOrder->user;


        $user->notify(new UserWithdrawRejectNotification($userWithdrawOrder, $user->local));


        return $userWithdrawOrder;
    }

    /**
     * 提现订单退款
     * @param UserWithdrawOrder $userWithdrawOrder
     */
    public function refundWithdrawOrder(UserWithdrawOrder $userWithdrawOrder)
    {

        abort_if(!in_array($userWithdrawOrder->order_status, [WithdrawOrderStatusType::CheckError, WithdrawOrderStatusType::PayError]), 400, '当前订单状态无法操作');

        $user = $userWithdrawOrder->user;

        $fee = $userWithdrawOrder->amount;

        $wallet_type = WalletType::fromValue($userWithdrawOrder->wallet_type)->value;

        $walletService = new WalletService();

        $walletService->deposit($user, $fee, $wallet_type, WalletLogSlug::refund, WalletLogType::DepositWithdrawErrorRefund, UserWithdrawOrder::class, $userWithdrawOrder->id, function (Wallet $wallet, WalletLog $walletLog) use ($fee, $wallet_type, $user, $userWithdrawOrder) {
            $userWithdrawOrder->order_status = WithdrawOrderStatusType::CheckErrorAndRefund;
            $userWithdrawOrder->back_wallet_log_id = $walletLog->id;
            $userWithdrawOrder->save();
        });

        $user->notify(new UserWithdrawRefundNotification($userWithdrawOrder));

    }

    /**
     * 提现订单发起付款
     * @param UserWithdrawOrder $userWithdrawOrder
     */
    public function paymentWithdrawOrder(UserWithdrawOrder $userWithdrawOrder)
    {
        $channel = $userWithdrawOrder->withdrawChannel;

        //区分当前提现通道
        switch ($channel->slug) {
            case PlatformType::LaoSun:
                $api_key = $channel->configValue('api_key');
                $api_key_v2 = $channel->configValue('api_key_v2');
                $merchant_id = $channel->configValue('merchant_id');
                LaoSunService::make()->setConfig($api_key, $api_key_v2, $merchant_id)->cash($userWithdrawOrder);
                break;
            case PlatformType::PayTM:
                OnlinePayService::make()->paytmCashPayOut($userWithdrawOrder, $channel);
                break;
            case PlatformType::IPayIndian:
                IPayIndianService::make()->payOut($userWithdrawOrder, $channel);
                break;
            case PlatformType::FPay:
                FPayTHBService::make()->withConfigWithdraw($channel)->payOut($userWithdrawOrder);
                break;
            case PlatformType::Yudrsu:
                YudrsuService::make()->payOut($userWithdrawOrder, $channel);
                break;
            case PlatformType::JstPay:
                JstPayService::make()->payOut($userWithdrawOrder, $channel);
                break;
            case PlatformType::BananaPay:
                BananaPayService::make()->payOut($userWithdrawOrder, $channel);
                break;
            case PlatformType::IvnPay:
                IvnPayService::make()->payOut($userWithdrawOrder, $channel);
                break;
            case PlatformType::PayPlus:
                PayPlusService::make()->payOut($userWithdrawOrder, $channel);
                break;
            default:
                abort(400, "当前代付渠道暂未接入");
                break;
        }

        $user = $userWithdrawOrder->user;
        $user->notify(new UserWithdrawToPayNotification($userWithdrawOrder));

    }

    /**
     * @param UserWithdrawOrder $userWithdrawOrder
     */
    public function checkStatusWithdrawOrder(UserWithdrawOrder $userWithdrawOrder)
    {
        $channel = $userWithdrawOrder->withdrawChannel;

        switch ($channel->slug) {
            case PlatformType::PayTM:
                PaytmCashService::make()->withdrawalResult($userWithdrawOrder, $channel);
                break;
            case PlatformType::IPayIndian:
                IPayIndianService::make()->withdrawalResult($userWithdrawOrder, $channel);
                break;
            default:
                abort(400, "不支持查询");
                break;
        }

    }

    /**
     * 提现打款成功
     * @param UserWithdrawOrder $userWithdrawOrder
     */
    public function withdrawOrderSuccess(UserWithdrawOrder $userWithdrawOrder)
    {
        \DB::beginTransaction();
        try {
            $userWithdrawOrder->is_pay = true;
            $userWithdrawOrder->pay_time = now();
            $userWithdrawOrder->order_status = WithdrawOrderStatusType::CheckSuccess;
            $userWithdrawOrder->save();
            /**@var User|Builder $user */
            $user = $userWithdrawOrder->user;

            if ($userWithdrawOrder->wallet_type === WalletType::balance) {
                //添加用户提现次数
                $user->increment('withdraw_count');//用户表提现次数
                //用户钱包表统计
                $user->walletCount()->increment('balance_withdraw', $userWithdrawOrder->actual_amount);
                //用户关系表统计
                $user->invite()->increment('withdraw_count');//提现次数
                $user->invite()->increment('balance_withdraw', $userWithdrawOrder->actual_amount);//提现金额
            }

            if ($userWithdrawOrder->wallet_type === WalletType::usdt) {
                //添加用户提现次数
                $user->increment('withdraw_count');//用户表提现次数
                //用户钱包表统计
                $user->walletCount()->increment('usdt_balance_withdraw', $userWithdrawOrder->actual_amount);
                //用户关系表统计
                $user->invite()->increment('withdraw_count');//提现次数
                $user->invite()->increment('usdt_balance_withdraw', $userWithdrawOrder->actual_amount);//提现金额
            }
            \DB::commit();
            //触发提现成功钩子
            UserHookService::make()->withdrawHook($userWithdrawOrder);

        } catch (\Exception $exception) {
            abort(400, $exception->getMessage());
        }

    }

    /**
     * 提现打款失败
     * @param UserWithdrawOrder $userWithdrawOrder
     */
    public function withdrawOrderError(UserWithdrawOrder $userWithdrawOrder)
    {
        $userWithdrawOrder->order_status = WithdrawOrderStatusType::PayError;
        $userWithdrawOrder->save();

        $user = $userWithdrawOrder->user;
        $user->notify(new UserWithdrawToPayErrorNotification($userWithdrawOrder, $user->local));

    }

    /**
     * 用户钱包扣款，创建用户提现订单
     * @param $user
     * @param WithdrawChannel $channel
     * @param WithdrawChannelList|null $channel_item
     * @param $data
     * @param float $amount
     */
    private function createWithdrawOrder($user, WithdrawChannel $channel, ?WithdrawChannelList $channel_item, $data, float $amount)
    {
        $walletService = new WalletService();

        //计算提现金额
        $amount = (float)$amount;
        $rate = $channel->rate;//手续费比例
        $rate_amount = $amount * ($rate / 100);//收费金额
        $actual_amount = $amount;//实际到账金额
        $amount = $amount + $rate_amount;

        switch ($channel->type) {
            case WithdrawChannelType::USDT_TRC20:
                //USDT提现
                $walletService->withdraw($user, $amount, WalletType::usdt, WalletLogSlug::withdraw, WalletLogType::WithdrawUSDTWithdraw, WithdrawChannel::class, $channel->id, function (Wallet $wallet, WalletLog $walletLog) use ($channel_item, $channel, $data, $rate_amount, $rate, $actual_amount, $amount, $user) {

                    $userWithdrawOrder = $this->createWithdrawOrderData($user, WalletType::usdt, $amount, $actual_amount, $rate, $rate_amount, $data, $channel, $channel_item);
                    $userWithdrawOrder->wallet_log_id = $walletLog->id;
                    $userWithdrawOrder->save();


                });
                //TODO 提现申请成功

                break;
            case WithdrawChannelType::OnLine:
                //在线法币提现
                $walletService->withdraw($user, $amount, WalletType::balance, WalletLogSlug::withdraw, WalletLogType::WithdrawOnlineWithdraw, WithdrawChannel::class, $channel->id, function (Wallet $wallet, WalletLog $walletLog) use ($channel_item, $channel, $data, $rate_amount, $rate, $actual_amount, $amount, $user) {

                    $userWithdrawOrder = $this->createWithdrawOrderData($user, WalletType::balance, $amount, $actual_amount, $rate, $rate_amount, $data, $channel, $channel_item);
                    $userWithdrawOrder->wallet_log_id = $walletLog->id;
                    $userWithdrawOrder->save();


                });
                //TODO 提现申请成功

                break;
        }

    }

    /**
     * 订单数据入库
     * @param $user
     * @param $wallet_type
     * @param $amount
     * @param $actual_amount
     * @param $rate
     * @param $rate_amount
     * @param $input_data
     * @param WithdrawChannel $channel
     * @param WithdrawChannelList|null $channel_item
     * @return UserWithdrawOrder|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
     */
    private function createWithdrawOrderData($user, $wallet_type, $amount, $actual_amount, $rate, $rate_amount, $input_data, WithdrawChannel $channel, ?WithdrawChannelList $channel_item)
    {
        $snowflake = new Snowflake;

        $order_sn = $snowflake->id();

        return UserWithdrawOrder::query()->create([
            'order_sn' => $order_sn,
            'user_id' => $user->id,
            'user_level' => $user->invite->level,
            'channel_id' => $user->channel_id,
            'link_id' => $user->link_id,
            'wallet_type' => $wallet_type,
            'amount' => $amount,
            'actual_amount' => $actual_amount,
            'rate' => $rate,
            'rate_amount' => $rate_amount,
            'input_data' => $input_data,
            'withdraw_type' => $channel->type,
            'withdraw_channel_id' => $channel->id,
            'withdraw_channel_item_id' => $channel_item?->id ?? 0,
            'order_status' => WithdrawOrderStatusType::Checking,
            'auto_check' => 0,
            'local' => $user->local,
            'lang' => $user->lang,
            'ip' => $this->getIP(),
            'imei' => $this->getIMEI(),
        ]);
    }

    /**
     * 检测提现金额
     * @param $amount
     * @param WithdrawChannel $channel
     * @param WithdrawChannelList|null $channel_item
     */
    public function checkAmount($amount, WithdrawChannel $channel, ?WithdrawChannelList $channel_item)
    {
        //渠道充值金额限定
        $min_money = $channel->min_money;
        $max_money = $channel->max_money;

        if ($channel_item) {
            $i_min_money = $channel_item->min_money;
            $i_max_money = $channel_item->max_money;
            //渠道选项充值金额限制
            $min_money = $i_min_money > $min_money ? $i_min_money : $min_money;
            $max_money = $i_max_money > $max_money ? $i_max_money : $max_money;
        }
        //判断最低金额
        abort_if($amount < $min_money, 400, Lang('单笔最少提现', [$min_money]));
        abort_if($max_money > 0 && $amount > $max_money, 400, Lang('单笔最多提现', [$max_money]));

    }

    /**
     * 提现输入信息
     * @param WithdrawChannel $channel
     * @param WithdrawChannelList|null $channel_item
     * @param $input_data
     * @return array
     */
    private function checkInputData(WithdrawChannel $channel, ?WithdrawChannelList $channel_item, $input_data)
    {
        $input_config = $channel->input_config;
        if ($channel_item) {
            $input_config = $channel_item->input_config;
        }
        $names = collect($input_config)->pluck('name')->toArray();
        $data = [];
        foreach ($names as $name) {
            $value = data_get($input_data, $name);
            abort_if(empty($value), 400, "error " . $name);
            $data[$name] = $value;
        }
        return $data;
    }


    /**
     * 提现扣除赠送金
     * @param UserWithdrawOrder $userWithdrawOrder
     */
    public function deductAwardHandle(UserWithdrawOrder $userWithdrawOrder)
    {

        //获取当前用户需要扣除的赠送金
        $user = $userWithdrawOrder->user;


        $amount = (float)$userWithdrawOrder->actual_amount;
        if ($userWithdrawOrder->wallet_type == WalletType::usdt) {
            $amount = $amount * (float)Setting('usdt_money_rate');
        }
        //扣除自己的赠送金
        $this->deductAwardHandleFn($user, $amount, $userWithdrawOrder, WalletLogType::WithdrawDeductRechargeAward, 0);


        //扣除上级的赠送金
        for ($i = 1; $i <= 10; $i++) {
            $invite_id = data_get($user->invite, 'invite_id_' . $i, 0);

            //有上级
            try {
                if ($invite_id > 0) {
                    $invite_user = User::query()->find($invite_id);
                    $deduct_money = $this->deductAwardHandleFn($invite_user, $amount, $userWithdrawOrder, WalletLogType::WithdrawFriendDeductRechargeAward, $i);
                    //上级有被扣除赠送金
                    if ($deduct_money > 0) {
                        //上级统计数据下级产生赠送金余额扣除
                        $invite_user->inviteAward()->decrement('give_balance', $deduct_money);
                        //扣除当前用户统计给上级产生的赠送金
                        $user->inviteAward()->decrement('p_' . $i . '_give_balance', $deduct_money);
                    }
                }
            } catch (\Exception $exception) {
                \Log::error("扣除上级赠送金失败:" . $exception->getMessage(), [
                    'invite_id' => $invite_id,
                    'order_sn' => $userWithdrawOrder->order_sn,
                    'level' => $i,
                    'amount' => $amount,
                ]);
            }

        }

    }

    private function deductAwardHandleFn(User $user, $amount, $userWithdrawOrder, $action_type, $level = 0)
    {
        $walletService = new WalletService();

        list($deduct_money, $deduct_items, $beyond_money) = $this->deductAward($user, $amount, $level);

        if ($deduct_money > 0) {
            //判断用户余额是否够扣除
            $user_give_balance = $user->wallet->give_balance;

            //不够，需要强制从赚钱宝里转出
            if ($user_give_balance < $deduct_money) {
                $residue = $deduct_money - $user_give_balance;
                $money_bao_give_balance = $user->moneyBao->give_balance;
                //转出金额
                $out_money = 0;
                //赚钱宝的钱够
                if ($money_bao_give_balance >= $residue) {
                    $out_money = $residue;
                } else {
                    $out_money = $money_bao_give_balance;
                }
                MoneyBaoService::make()->takeOutMoneyBao($user, $out_money, WalletType::give);

                //重新计算要扣除的赠送金，防止余额不足
                $deduct_money = $user_give_balance + $out_money;
            }
            //钱包扣款
            $walletService->withdraw($user, $deduct_money, WalletType::give, WalletLogSlug::deduct, $action_type, UserWithdrawOrder::class, $userWithdrawOrder->id, function () use ($deduct_items) {

            });


            //赠送金扣款记录修改
            foreach ($deduct_items as $item) {
                $uar = UserAwardRecord::query()->find(data_get($item, 'id'));
                if ($uar) {
                    $kc = (float)data_get($item, 'amount');
                    $uar->decrement('residue_amount', $kc);
                    $uar->increment('deduct_count');
                }
            }

            //发送扣除赠送金通知
            if ($action_type === WalletLogType::WithdrawFriendDeductRechargeAward) {
                $user->notify(new UserFriendDeductAwardNotification($userWithdrawOrder, $deduct_money));
            } else {
                $user->notify(new UserDeductAwardNotification($userWithdrawOrder, $deduct_money));
            }


        }
        return $deduct_money;
    }


    /**
     * 获取提现要扣除的奖励信息
     * @param $user
     * @param $money
     * @return array
     */
    public function deductAward($user, $money, $level = 0)
    {
        $orm = UserAwardRecord::query()
            ->where('user_id', $user->id)
            ->where('is_deduct', true)
            ->orderByDesc('rate')
            ->where('residue_amount', '>', 0);

        if ($level > 0) {
            $orm->where('level', '>', 0);
        } else {
            $orm->where('level', 0);
        }
        $list = $orm->get();
        //需要扣除的金额
        $deduct_money = 0;
        //需要扣除的记录
        $deduct_items = collect();
        //超出的金额
        $beyond_money = 0;
        foreach ($list as $key => $item) {
            list($item_deduct_money, $residue_money) = $this->deductAwardItem($money, $item, $deduct_items);
            $deduct_money += $item_deduct_money;
            $beyond_money = $residue_money;
            if ($residue_money <= 0) break;
        }
        return [$deduct_money, $deduct_items, $beyond_money];
    }


    private function deductAwardItem($money, $item, $deduct_items)
    {
        //计算当前记录需要扣除的金额
        $rate = $item->rate / 100;
        $deduct_money = $rate * $money;
        //当前记录不够扣
        if ($deduct_money > $item->residue_amount) {
            $item_deduct_money = $item->residue_amount;
            $deduct_items->add(['id' => $item->id, 'amount' => $item->residue_amount]);
            //算回剩下需要扣的提现金额
            $residue_money = ($deduct_money - $item->residue_amount) / $rate;
        } else {
            //当期记录够扣除
            $item_deduct_money = $deduct_money;
            $deduct_items->add(['id' => $item->id, 'amount' => $deduct_money]);
            //剩下需要扣的提现金额
            $residue_money = 0;
        }
        return [$item_deduct_money, $residue_money];

    }

    public function checkData(UserWithdrawOrder $userWithdrawOrder)
    {
        $input_data = $userWithdrawOrder->input_data;

        $c = [];

        foreach ($input_data as $key => $value) {
            $list = UserWithdrawOrder::query()->where('input_data->' . $key, $value)->get(['user_id', 'amount', 'order_status', 'id']);
            $c[$key]['count'] = collect($list)->count();
            $c[$key]['user_count'] = collect($list)->groupBy('user_id')->count();
            $c[$key]['success_count'] = collect($list)->filter(fn($item) => $item->order_status == WithdrawOrderStatusType::CheckSuccess)->count();
        }


        $userWithdrawOrder->checkData()->updateOrCreate([], $c);

    }

}
