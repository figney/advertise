<?php


namespace App\Services;


use App\Enums\MoneyBaoStatusType;
use App\Enums\WalletLogSlug;
use App\Enums\WalletLogType;
use App\Enums\WalletType;
use App\Models\MoneyBao;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletLog;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;

class MoneyBaoService extends BaseService
{


    /**
     * 钱包转入赚钱宝
     * @param User $user
     * @param $fee
     * @param $wallet_type
     */
    public function depositMoneyBao($user, $fee, $wallet_type)
    {
        $walletService = new WalletService();

        abort_if($wallet_type !== WalletType::give, 400, Lang("只能使用赠送金"));

        abort_if($this->getStatus($user, $wallet_type), 400, Lang('收益未领取无法存入'));


        switch ($wallet_type) {
            case WalletType::balance:
                //钱包扣款
                $walletService->withdraw($user, $fee, WalletType::balance, WalletLogSlug::deposit, WalletLogType::WithdrawWalletToMoneyBao, User::class, $user->id, function () use ($fee, $user) {
                    //赚钱宝加款
                    $user->moneyBao->increment('balance', $fee);

                    $user->moneyBao->update([
                        'last_time' => now(),
                        'balance_status' => MoneyBaoStatusType::working,
                    ]);

                });

                break;
            case WalletType::usdt:
                $walletService->withdraw($user, $fee, WalletType::usdt, WalletLogSlug::deposit, WalletLogType::WithdrawWalletToMoneyBao, User::class, $user->id, function () use ($fee, $user) {
                    $user->moneyBao->increment('usdt_balance', $fee);
                    $user->moneyBao->update([
                        'usdt_last_time' => now(),
                        'usdt_status' => MoneyBaoStatusType::working,
                    ]);
                });

                break;
            case WalletType::give:
                //检查次数
                /*if ($user->recharge_count <= 0) {

                    $count = $user->walletLogs()->where('action_type', WalletLogType::WithdrawWalletToMoneyBao)
                        ->where('wallet_type', WalletType::give)
                        ->whereDate('created_at', Carbon::now())
                        ->count();

                    abort_if($count >= 10, 400, Lang('存入次数不足'));

                }*/


                $walletService->withdraw($user, $fee, WalletType::give, WalletLogSlug::deposit, WalletLogType::WithdrawWalletToMoneyBao, User::class, $user->id, function () use ($fee, $user) {
                    $user->moneyBao->increment('give_balance', $fee);
                    $user->moneyBao->update([
                        'give_last_time' => now(),
                        'give_status' => MoneyBaoStatusType::working,
                    ]);
                });

                break;
        }
    }

    /**
     * 赚钱宝转出到钱包
     * @param User $user
     * @param $fee
     * @param $wallet_type
     */
    public function takeOutMoneyBao($user, $fee, $wallet_type)
    {
        $walletService = new WalletService();
        abort_if(!$walletService->checkWalletType($wallet_type), 400, Lang("类型错误"));

        \DB::beginTransaction();
        $moneyBao = MoneyBao::query()->where('user_id', $user->id)->lockForUpdate()->first();

        switch ($wallet_type) {
            case WalletType::balance:
                //判断余额
                abort_if($fee > $moneyBao->balance, 400, Lang("余额不足"));

                //钱包加款
                $walletService->deposit($user, $fee, WalletType::balance, WalletLogSlug::takeOut, WalletLogType::DepositMoneyBaoToWallet, User::class, $user->id, function () use ($fee, $moneyBao) {
                    //赚钱宝扣款
                    $moneyBao->decrement('balance', $fee);
                });

                break;
            case WalletType::usdt:
                abort_if($fee > $moneyBao->usdt_balance, 400, Lang("余额不足"));

                //钱包加款
                $walletService->deposit($user, $fee, WalletType::usdt, WalletLogSlug::takeOut, WalletLogType::DepositMoneyBaoToWallet, User::class, $user->id, function () use ($fee, $moneyBao) {
                    //赚钱宝扣款
                    $moneyBao->decrement('usdt_balance', $fee);
                });

                break;
            case WalletType::give:
                abort_if($fee > $moneyBao->give_balance, 400, Lang("余额不足"));

                //钱包加款
                $walletService->deposit($user, $fee, WalletType::give, WalletLogSlug::takeOut, WalletLogType::DepositMoneyBaoToWallet, User::class, $user->id, function () use ($fee, $moneyBao) {
                    //赚钱宝扣款
                    $moneyBao->decrement('give_balance', $fee);
                });
                break;
        }
        \DB::commit();

    }


    /**
     * 领取赚钱宝收益
     * @param User $user
     * @param $wallet_type
     */
    public function receiveMoneyBaoAward($user, $wallet_type)
    {

        switch ($wallet_type) {
            case WalletType::balance:
                if ($user->moneyBao->usdt_balance > 0) {
                    //最后存入时间
                    $last_time = $user->moneyBao->last_time ?: $user->moneyBao->last_grant_time;
                    if (Carbon::make($last_time)->addDays()->lte(now()) && !in_array($user->moneyBao->balance_status, [MoneyBaoStatusType::stop])) {
                        $this->grantInterestByType($user->moneyBao, WalletType::balance);
                        return;
                    }
                }
                abort(400, Lang('ERROR'));
                break;
            case WalletType::usdt:
                if ($user->moneyBao->usdt_balance > 0) {
                    //最后存入时间
                    $usdt_last_time = $user->moneyBao->usdt_last_time ?: $user->moneyBao->last_grant_time;
                    if (Carbon::make($usdt_last_time)->addDays()->lte(now()) && !in_array($user->moneyBao->usdt_status, [MoneyBaoStatusType::stop])) {
                        $this->grantInterestByType($user->moneyBao, WalletType::usdt);
                        return;
                    }
                }
                abort(400, Lang('ERROR'));
                break;
            case WalletType::give:
                if ($user->moneyBao->give_balance > 0) {
                    //最后存入时间
                    $give_last_time = $user->moneyBao->give_last_time ?: $user->moneyBao->last_grant_time;
                    if (Carbon::make($give_last_time)->addDays()->lte(now()) && !in_array($user->moneyBao->give_status, [MoneyBaoStatusType::stop])) {
                        $this->grantInterestByType($user->moneyBao, WalletType::give);
                        return;
                    }
                }
                abort(400, Lang('ERROR'));
                break;
        }

    }


    private function getStatus($user, $wallet_type)
    {
        switch ($wallet_type) {
            case WalletType::balance:
                if ($user->moneyBao->usdt_balance > 0) {
                    //最后存入时间
                    $last_time = $user->moneyBao->last_time ?: $user->moneyBao->last_grant_time;
                    if (Carbon::make($last_time)->addDays()->lte(now())) return true;
                }
                break;
            case WalletType::usdt:
                if ($user->moneyBao->usdt_balance > 0) {
                    //最后存入时间
                    $usdt_last_time = $user->moneyBao->usdt_last_time ?: $user->moneyBao->last_grant_time;
                    if (Carbon::make($usdt_last_time)->addDays()->lte(now())) return true;
                }
                break;
            case WalletType::give:
                if ($user->moneyBao->give_balance > 0) {
                    //最后存入时间
                    $give_last_time = $user->moneyBao->give_last_time ?: $user->moneyBao->last_grant_time;
                    if (Carbon::make($give_last_time)->addDays()->lte(now())) return true;
                }
                break;
        }
        return false;
    }


    /**
     * 发放赚钱宝利息
     */
    public function checkGrantInterest()
    {
        //查询需要发放利息的赚钱宝对象

        MoneyBao::query()
            ->where('has', 1)
            ->where('last_grant_time', '<', Carbon::today())
            ->chunkById(300, function ($moneyBaoList) {
                foreach ($moneyBaoList as $moneyBao) {
                    //队列处理
                    //dispatch(new MoneyBaoGrantInterest($moneyBao))->onQueue(QueueType::moneyBao);
                    //直接发放
                    //$this->grantInterest($moneyBao);
                }
            });
    }

    public function grantInterestByType(MoneyBao $moneyBao, $wallet_type)
    {

        try {
            $user = $moneyBao->user;
            $walletService = new WalletService();
            //计算每种金额每天的利息
            $mb_balance_rate = (float)Setting('mb_balance_rate');//法币年化
            $mb_usdt_rate = (float)Setting('mb_usdt_rate');//USDT年化
            $mb_give_rate = (float)Setting('mb_give_rate');//赠送金年化

            $balance = (float)$moneyBao->balance;
            if ($balance > 0 && $wallet_type == WalletType::balance) {
                //现金利息存入现金余额
                $day_mb_balance_rate = $mb_balance_rate / 365 / 100;
                $fee = $balance * $day_mb_balance_rate;
                $walletService->deposit($user, $fee, WalletType::balance, WalletLogSlug::interest, WalletLogType::DepositMoneyBaoInterestByBalance, MoneyBao::class, $user->id, function (Wallet $wallet, WalletLog $walletLog) use ($fee, $moneyBao) {
                    //更新赚钱宝数据统计
                    $moneyBao->increment("balance_interest", $fee);
                    $moneyBao->increment("balance_earnings", $fee);
                    $moneyBao->last_grant_time = now();
                    $moneyBao->balance_status = MoneyBaoStatusType::stop;
                    $moneyBao->save();

                });
                $this->takeOutMoneyBao($user, $balance, WalletType::balance);

            }
            $usdt_balance = (float)$moneyBao->usdt_balance;
            if ($usdt_balance > 0 && $wallet_type == WalletType::usdt) {
                //usdt利息存入USDT余额
                $day_mb_usdt_rate = $mb_usdt_rate / 365 / 100;
                $fee = $usdt_balance * $day_mb_usdt_rate;

                $walletService->deposit($user, $fee, WalletType::usdt, WalletLogSlug::interest, WalletLogType::DepositMoneyBaoInterestByUSDT, MoneyBao::class, $user->id, function (Wallet $wallet, WalletLog $walletLog) use ($fee, $moneyBao) {
                    //更新赚钱宝数据统计
                    $moneyBao->increment("usdt_balance_interest", $fee);
                    $moneyBao->increment("usdt_balance_earnings", $fee);
                    $moneyBao->last_grant_time = now();
                    $moneyBao->usdt_status = MoneyBaoStatusType::stop;
                    $moneyBao->save();
                });
                $this->takeOutMoneyBao($user, $usdt_balance, WalletType::usdt);

            }

            $give_balance = (float)$moneyBao->give_balance;
            if ($give_balance > 0 && $wallet_type == WalletType::give) {
                //赠送金利息存入现金余额
                $day_mb_give_rate = $mb_give_rate / 365 / 100;
                $fee = $give_balance * $day_mb_give_rate;
                $walletService->deposit($user, $fee, WalletType::balance, WalletLogSlug::interest, WalletLogType::DepositMoneyBaoInterestByGive, MoneyBao::class, $user->id, function (Wallet $wallet, WalletLog $walletLog) use ($fee, $moneyBao) {
                    //更新赚钱宝数据统计
                    $moneyBao->increment("give_balance_earnings", $fee);
                    $moneyBao->last_grant_time = now();
                    $moneyBao->give_status = MoneyBaoStatusType::stop;
                    $moneyBao->save();
                    //更新钱包数据统计
                    $wallet->walletCount()->increment("give_balance_earnings", $fee);
                });
                $this->takeOutMoneyBao($user, $give_balance, WalletType::give);

            }
        } catch (\Exception $exception) {
            \Log::error("赚钱宝利息发放错误：" . $exception->getMessage(), $moneyBao->toArray());
        }
    }

    /**
     * 每日利息发放
     * @param MoneyBao|Builder $moneyBao
     */
    public function grantInterest(MoneyBao $moneyBao)
    {
        return;

        try {
            $user = $moneyBao->user;
            $walletService = new WalletService();
            //计算每种金额每天的利息
            $mb_balance_rate = (float)Setting('mb_balance_rate');//法币年化
            $mb_usdt_rate = (float)Setting('mb_usdt_rate');//USDT年化
            $mb_give_rate = (float)Setting('mb_give_rate');//赠送金年化

            $balance = (float)$moneyBao->balance;
            if ($balance > 0) {
                //现金利息存入现金余额
                $day_mb_balance_rate = $mb_balance_rate / 365 / 100;
                $fee = $balance * $day_mb_balance_rate;
                $walletService->deposit($user, $fee, WalletType::balance, WalletLogSlug::interest, WalletLogType::DepositMoneyBaoInterestByBalance, MoneyBao::class, $user->id, function (Wallet $wallet, WalletLog $walletLog) use ($fee, $moneyBao) {
                    //更新赚钱宝数据统计
                    $moneyBao->increment("balance_interest", $fee);
                    $moneyBao->increment("balance_earnings", $fee);
                    $moneyBao->last_grant_time = now();
                    $moneyBao->save();

                });
                $this->takeOutMoneyBao($user, $balance, WalletType::balance);

            }
            $usdt_balance = (float)$moneyBao->usdt_balance;
            if ($usdt_balance > 0) {
                //usdt利息存入USDT余额
                $day_mb_usdt_rate = $mb_usdt_rate / 365 / 100;
                $fee = $usdt_balance * $day_mb_usdt_rate;

                $walletService->deposit($user, $fee, WalletType::usdt, WalletLogSlug::interest, WalletLogType::DepositMoneyBaoInterestByUSDT, MoneyBao::class, $user->id, function (Wallet $wallet, WalletLog $walletLog) use ($fee, $moneyBao) {
                    //更新赚钱宝数据统计
                    $moneyBao->increment("usdt_balance_interest", $fee);
                    $moneyBao->increment("usdt_balance_earnings", $fee);
                    $moneyBao->last_grant_time = now();
                    $moneyBao->save();
                });
                $this->takeOutMoneyBao($user, $usdt_balance, WalletType::usdt);

            }

            $give_balance = (float)$moneyBao->give_balance;
            if ($give_balance > 0) {
                //赠送金利息存入现金余额
                $day_mb_give_rate = $mb_give_rate / 365 / 100;
                $fee = $give_balance * $day_mb_give_rate;
                $walletService->deposit($user, $fee, WalletType::balance, WalletLogSlug::interest, WalletLogType::DepositMoneyBaoInterestByGive, MoneyBao::class, $user->id, function (Wallet $wallet, WalletLog $walletLog) use ($fee, $moneyBao) {
                    //更新赚钱宝数据统计
                    $moneyBao->increment("give_balance_earnings", $fee);
                    $moneyBao->last_grant_time = now();
                    $moneyBao->save();
                    //更新钱包数据统计
                    $wallet->walletCount()->increment("give_balance_earnings", $fee);
                });
                $this->takeOutMoneyBao($user, $give_balance, WalletType::give);

            }
        } catch (\Exception $exception) {
            \Log::error("赚钱宝利息发放错误：" . $exception->getMessage(), $moneyBao->toArray());
        }
    }

}
