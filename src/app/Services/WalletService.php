<?php


namespace App\Services;


use App\Enums\WalletLogSlug;
use App\Enums\WalletLogType;
use App\Enums\WalletType;
use App\Models\User;
use App\Models\UserInviteLog;
use App\Models\Wallet;
use App\Models\WalletLog;
use App\Models\WalletLogMongo;
use Illuminate\Database\Query\Builder;

class WalletService extends BaseService
{

    /**
     * 获取用户钱包信息加锁
     * @param User $user
     * @return Wallet|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|object|null
     */
    public function getWalletLock(User $user)
    {
        \DB::beginTransaction();
        $wallet = Wallet::query()->where('user_id', $user->id)->with(['walletCount'])->lockForUpdate()->first();
        \DB::commit();
        return $wallet;
    }

    /**
     * 加款
     * @param User|Builder $user
     * @param  $fee
     * @param $wallet_type
     * @param $wallet_slug
     * @param  $action_type
     * @param $target_type
     * @param $target_id
     * @param \Closure $closure
     * @return array
     */
    public function deposit($user, $fee, $wallet_type, $wallet_slug, $action_type, $target_type, $target_id, $closure = null)
    {


        abort_if(!$this->checkType($wallet_type, $wallet_slug, $action_type), 400, Lang("类型错误"));
        //获取钱包
        $wallet = Wallet::query()->where('user_id', $user->id)->with(['walletCount'])->first();
        \DB::beginTransaction();

        $before_fee = 0;
        switch ($wallet_type) {
            case WalletType::balance:
                $before_fee = $wallet->balance;
                //钱包余额加款
                $wallet->increment('balance', $fee);
                //钱包统计余额流水总收入加款
                $wallet->walletCount()->increment('balance_income', $fee);

                //如果是充值，当前金额总充值记录
                if ($wallet_slug == WalletLogSlug::recharge) {
                    //添加用户充值次数
                    $user->increment('recharge_count');//充值次数
                    //用户钱包表统计
                    $wallet->walletCount()->increment('balance_recharge', $fee);//充值金额
                    //用户关系表统计
                    $user->invite()->increment('recharge_count');//充值次数
                    $user->invite()->increment('balance_recharge', $fee);//充值金额
                }
                //如果是利息，
                if ($wallet_slug == WalletLogSlug::interest) {
                    //用户钱包表统计
                    $wallet->walletCount()->increment('balance_interest', $fee);//+利息统计
                    $wallet->walletCount()->increment('balance_earnings', $fee);//+总收益
                    //用户关系表统计
                    $user->invite()->increment('balance_earnings', $fee);//+总收益
                }
                //如果是利息，
                if ($wallet_slug == WalletLogSlug::commission) {
                    //用户钱包表统计
                    $wallet->walletCount()->increment('balance_commission', $fee);//+佣金统计
                    $wallet->walletCount()->increment('balance_earnings', $fee);//+总收益
                }

                break;
            case WalletType::usdt:
                $before_fee = $wallet->usdt_balance;
                //钱包USDT_余额加款
                $wallet->increment('usdt_balance', $fee);
                //钱包统计usdt_余额流水总收入加款
                $wallet->walletCount()->increment('usdt_balance_income', $fee);

                //如果是充值，当前金额总充值记录
                if ($wallet_slug == WalletLogSlug::recharge) {
                    //添加用户充值次数
                    $user->increment('recharge_count');
                    //用户钱包表统计
                    $wallet->walletCount()->increment('usdt_balance_recharge', $fee);
                    //用户关系表统计
                    $user->invite()->increment('recharge_count');//充值次数
                    $user->invite()->increment('usdt_balance_recharge', $fee);//充值USDT金额
                }
                //如果是利息，
                if ($wallet_slug == WalletLogSlug::interest) {
                    //用户钱包表统计
                    $wallet->walletCount()->increment('usdt_balance_interest', $fee);//+利息统计
                    $wallet->walletCount()->increment('usdt_balance_earnings', $fee);//+总收益
                    //用户关系表统计
                    $user->invite()->increment('usdt_balance_earnings', $fee);//+总收益
                }
                //如果是利息，
                if ($wallet_slug == WalletLogSlug::commission) {
                    //用户钱包表统计
                    $wallet->walletCount()->increment('usdt_balance_commission', $fee);//+佣金统计
                    $wallet->walletCount()->increment('usdt_balance_earnings', $fee);//+总收益
                }

                break;
            case WalletType::give:
                $before_fee = $wallet->give_balance;
                //钱包赠送金_余额加款
                $wallet->increment('give_balance', $fee);
                //钱包统计赠送金_余额流水总收入加款
                $wallet->walletCount()->increment('give_balance_income', $fee);

                //如果是奖励
                if ($wallet_slug == WalletLogSlug::award) {
                    //钱包统计赠送金累计奖励更新
                    $wallet->walletCount()->increment('give_balance_award', $fee);
                }

                break;
        }

        $walletLog = $this->walletLog($wallet, $wallet_type, $wallet_slug, $action_type, $before_fee, $fee, $target_type, $target_id);


        //执行业务逻辑闭包
        if ($closure instanceof \Closure) {
            call_user_func($closure, $wallet, $walletLog);
        }
        //提交事务
        \DB::commit();

        //触发利息收益发放钩子
        if ($wallet_slug == WalletLogSlug::interest) {
            UserHookService::make()->earningsHook($user, $walletLog);
        }

        $this->walletLogMongo($walletLog);

        return [$wallet, $walletLog];

    }

    /**
     * 扣款
     * @param User|Builder $user
     * @param $fee
     * @param $wallet_type
     * @param $wallet_slug
     * @param string $action_type
     * @param $target_type
     * @param $target_id
     * @param \Closure|null $closure
     * @return array
     */
    public function withdraw($user, $fee, $wallet_type, $wallet_slug, $action_type, $target_type, $target_id, $closure = null)
    {
        abort_if(!$this->checkType($wallet_type, $wallet_slug, $action_type), 400, Lang("类型错误"));
        //获取钱包
        $wallet = $this->getWalletLock($user);
        \DB::beginTransaction();
        $before_fee = 0;
        switch ($wallet_type) {
            case WalletType::balance:
                abort_if($wallet->balance < $fee, 400, Lang("余额不足"));
                $before_fee = $wallet->balance;
                //钱包余额扣款
                $wallet->decrement('balance', $fee);
                //钱包统计余额流水总支出
                $wallet->walletCount()->increment('balance_outcome', $fee);


                break;
            case WalletType::usdt:
                abort_if($wallet->usdt_balance < $fee, 400, Lang("余额不足"));
                $before_fee = $wallet->usdt_balance;
                //钱包USDT_余额扣款
                $wallet->decrement('usdt_balance', $fee);
                //钱包统计usdt_余额流水总支出
                $wallet->walletCount()->increment('usdt_balance_outcome', $fee);


                break;
            case WalletType::give:

                if ($wallet_slug == WalletLogSlug::deduct) {
                    if ($wallet->give_balance < $fee) {
                        \Log::error("赠送金扣除金额错误", ['fee' => $fee, 'give_balance' => $wallet->give_balance]);
                        $fee = $wallet->give_balance;
                    }
                }

                abort_if($wallet->give_balance < $fee, 400, Lang("余额不足"));
                $before_fee = $wallet->give_balance;
                //钱包赠送金_余额扣款
                $wallet->decrement('give_balance', $fee);
                //钱包统计赠送金_余额流水总支出
                $wallet->walletCount()->increment('give_balance_outcome', $fee);

                //如果是扣除
                if ($wallet_slug == WalletLogSlug::deduct) {
                    //钱包统计赠送金累计奖励更新
                    $wallet->walletCount()->decrement('give_balance_award', $fee);
                }

                break;
        }

        $walletLog = $this->walletLog($wallet, $wallet_type, $wallet_slug, $action_type, $before_fee, -$fee, $target_type, $target_id);

        //执行业务逻辑闭包
        if ($closure instanceof \Closure) {
            call_user_func($closure, $wallet, $walletLog);
        }
        //提交事务
        \DB::commit();

        $this->walletLogMongo($walletLog);

        return [$wallet, $walletLog];
    }

    /**
     * 钱包余额转换
     * @param User $user 用户
     * @param float|int $fee
     * @param boolean $toMoney
     */
    public function transform($user, $fee, bool $toMoney)
    {
        abort_if(!SettingBool('open_transform'), 400, Lang('钱包余额转换已关闭'));

        //获取钱包
        $wallet = $this->getWalletLock($user);
        //USDT汇率
        $usdt_money_rate = Setting("usdt_money_rate");
        //USDT转余额最小值
        $usdt_to_money_min = Setting("usdt_to_money_min");
        //余额转USDT最小值
        $money_to_usdt_min = Setting("money_to_usdt_min");

        $before_fee_usdt = $wallet->usdt_balance;
        $before_fee = $wallet->balance;

        $logs = collect();

        \DB::beginTransaction();
        //USDT转余额
        if ($toMoney) {
            abort_if($fee < $usdt_to_money_min, 400, Lang("最少需要USDT", [$usdt_to_money_min]));
            abort_if($fee > $wallet->usdt_balance, 400, Lang("USDT余额不足"));

            //转入的金额
            $balance = $fee * $usdt_money_rate;

            //扣除USDT
            $wallet->decrement('usdt_balance', $fee);
            //钱包统计usdt_余额流水总支出
            $wallet->walletCount()->increment('usdt_balance_outcome', $fee);
            //写入流水记录
            $walletLog = $this->walletLog($wallet, WalletType::usdt, WalletLogSlug::transform, WalletLogType::WithdrawUsdtToBalance, $before_fee_usdt, -$fee, Wallet::class, $wallet->user_id);

            $logs->add($walletLog);

            //添加余额
            $wallet->increment('balance', $balance);
            //钱包统计余额流水总收入加款
            $wallet->walletCount()->increment('balance_income', $balance);
            //写入流水记录
            $walletLog = $this->walletLog($wallet, WalletType::balance, WalletLogSlug::transform, WalletLogType::DepositUsdtToBalance, $before_fee, $balance, Wallet::class, $wallet->user_id);
            $logs->add($walletLog);
        } else {//余额转USDT
            abort_if($fee < $money_to_usdt_min, 400, Lang("最少需要", [$money_to_usdt_min, Setting("default_currency")]));
            abort_if($fee > $wallet->balance, 400, Lang("余额不足"));
            //转入的USDT
            $usdt = $fee / $usdt_money_rate;

            //扣除余额
            $wallet->decrement('balance', $fee);
            //钱包统计余额流水总支出
            $wallet->walletCount()->increment('balance_outcome', $fee);
            //写入流水记录
            $walletLog = $this->walletLog($wallet, WalletType::balance, WalletLogSlug::transform, WalletLogType::WithdrawBalanceToUsdt, $before_fee, -$fee, Wallet::class, $wallet->user_id);
            $logs->add($walletLog);
            //添加USDT
            $wallet->increment('usdt_balance', $usdt);
            //钱包统计usdt_余额流水总收入加款
            $wallet->walletCount()->increment('usdt_balance_income', $usdt);
            //写入流水记录
            $walletLog = $this->walletLog($wallet, WalletType::usdt, WalletLogSlug::transform, WalletLogType::DepositBalanceToUsdt, $before_fee_usdt, $usdt, Wallet::class, $wallet->user_id);
            $logs->add($walletLog);
        }

        \DB::commit();

        $logs->each(function ($walletLog) {
            $this->walletLogMongo($walletLog);
        });

        return [$wallet, $walletLog];
    }


    /**
     * 写入流水记录
     * @param Wallet $wallet
     * @param $wallet_type
     * @param $wallet_slug
     * @param $action_type
     * @param $before_fee
     * @param $fee
     * @param $target_type
     * @param $target_id
     * @return WalletLog|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
     */
    private function walletLog(Wallet $wallet, $wallet_type, $wallet_slug, $action_type, $before_fee, $fee, $target_type, $target_id)
    {
        $data = [
            'channel_id' => $wallet->channel_id,
            'link_id' => $wallet->link_id,
            'user_id' => $wallet->user_id,
            'user_level' => $wallet->user_level,
            'wallet_id' => $wallet->id,
            'wallet_type' => $wallet_type,
            'wallet_slug' => $wallet_slug,
            'action_type' => $action_type,
            'before_fee' => $before_fee,
            'fee' => $fee,
            'target_type' => $target_type,
            'target_id' => $target_id,
        ];
        return WalletLog::query()->create($data);
    }

    private function walletLogMongo(WalletLog $walletLog)
    {
        $data = [
            'wallet_log_id' => $walletLog->id,
            'channel_id' => $walletLog->channel_id,
            'link_id' => $walletLog->link_id,
            'user_id' => $walletLog->user_id,
            'user_level' => $walletLog->user_level,
            'wallet_id' => $walletLog->wallet_id,
            'wallet_type' => $walletLog->wallet_type,
            'wallet_slug' => $walletLog->wallet_slug,
            'action_type' => $walletLog->action_type,
            'before_fee' => (float)$walletLog->before_fee,
            'fee' => (float)$walletLog->fee,
            'target_type' => $walletLog->target_type,
            'target_id' => $walletLog->target_id,
        ];
        WalletLogMongo::query()->create($data);

    }


    /**
     * 检测类型
     * @param $wallet_type
     * @param $wallet_slug
     * @param $action_type
     * @return bool
     */
    public function checkType($wallet_type, $wallet_slug, $action_type): bool
    {
        if (in_array($action_type, WalletLogType::asArray()) && in_array($wallet_slug, WalletLogSlug::asArray()) && in_array($wallet_type, WalletType::asArray())) {
            return true;
        }
        return false;
    }

    /**
     * @param $wallet_type
     * @return bool
     */
    public function checkWalletType($wallet_type)
    {
        if (in_array($wallet_type, WalletType::asArray())) {
            return true;
        }
        return false;
    }
}
