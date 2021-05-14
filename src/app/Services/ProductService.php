<?php


namespace App\Services;


use App\Enums\ProductType;
use App\Enums\WalletLogSlug;
use App\Enums\WalletLogType;
use App\Enums\WalletType;
use App\Models\Notifications\UserProductCommissionNotification;
use App\Models\Notifications\UserProductCommissionV2Notification;
use App\Models\Notifications\UserProductOverNotification;
use App\Models\Product;
use App\Models\User;
use App\Models\UserInviteAward;
use App\Models\UserProduct;
use App\Models\Wallet;
use App\Models\WalletLog;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;

class ProductService extends BaseService
{

    /**
     * 获取列表
     * @return Product[]|array|\GeneaLabs\LaravelModelCaching\CachedBuilder[]|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|mixed.
     */
    public function getList()
    {
        return Product::query()->orderByDesc('order')->get()->makeHidden(['content', 'big_cover', 'select_money_list']);
    }

    /**
     * 获取单个产品
     * @param $id
     * @return Product|Product[]|array|\GeneaLabs\LaravelModelCaching\CachedBuilder|\GeneaLabs\LaravelModelCaching\CachedBuilder[]|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed|null
     */
    public function getItem($id)
    {
        return Product::query()->find($id);
    }


    /**
     * 获取用户定期产品数据
     */
    public function getUserProductData(User $user)
    {

        //
        $list = $user->products()->where(function ($q) {
            //未结束或者今天结束的
            $q->where('is_over', 0)->orWhereDate('over_day', Carbon::today());
        })->get();


        $b = collect($list)->filter(function (UserProduct $item) {
            return $item->product_type == ProductType::balance;
        })->map(function (UserProduct $item) {
            $r['today_estimate'] = data_get($item->dayData(), 'day_fee');
            $r['this_today_estimate'] = data_get($item->dayData(), 'this_fee');
            //如果结束了就不计算存入资产
            $r['amount'] = $item->is_over ? 0 : $item->amount;
            return $r;
        });


        $u = collect($list)->filter(function (UserProduct $item) {
            return $item->product_type == ProductType::usdt;
        })->map(function (UserProduct $item) {
            $r['today_estimate'] = data_get($item->dayData(), 'day_fee');
            $r['this_today_estimate'] = data_get($item->dayData(), 'this_fee');
            //如果结束了就不计算存入资产
            $r['amount'] = $item->is_over ? 0 : $item->amount;
            return $r;
        });


        $data[WalletType::balance] = [
            'today_estimate' => (float)$b->sum('today_estimate'),//今日预估收益结束值
            'this_today_estimate' => (float)$b->sum('this_today_estimate'),//今日预估收益开始值
            'property' => (float)$b->sum('amount'),//总资产
            'interest' => (float)$user->walletCount->product_balance_interest,//总收益
        ];
        $data[WalletType::usdt] = [
            'today_estimate' => (float)$u->sum('today_estimate'),//今日预估收益结束值
            'this_today_estimate' => (float)$u->sum('this_today_estimate'),//今日预估收益开始值
            'property' => (float)$u->sum('amount'),//总资产
            'interest' => (float)$user->walletCount->product_usdt_balance_interest,//总收益
        ];

        return $data;

    }


    /**
     * 用户钱包购买产品
     * @param User $user
     * @param Product $product
     * @param float $amount
     */
    public function buyProduct(User $user, Product $product, float $amount)
    {

        abort_if(!$product->status, 400, Lang('ERROR'));

        abort_if($amount <= 0, 400, Lang('ERROR'));

        //金额重新计算，单价x数量投资模式
        if ($product->is_number_buy) {
            abort_if($amount < 1, 400, Lang('购买数量错误'));
            $amount = (float)$amount * $product->min_money;
        }
        //判断起投金额
        abort_if($amount < $product->min_money, 400, Lang('投资金额不能少于', [(float)$product->min_money]));
        $walletService = new WalletService();

        //判断新用户
        if ($product->is_no_buy_user) {
            $buy_count = $user->products()->count();
            abort_if($buy_count > 0, 400, Lang('只允许新用户购买'));
        }

        //判断购买次数
        if ($product->user_max_buy > 0) {
            $buy_count = $user->products()->where('product_id', $product->id)->count();
            abort_if($buy_count >= $product->user_max_buy, 400, Lang('购买次数已满'));
        }

        //判断用户最大投资额
        if ($product->user_max_amount > 0) {
            $user_amount = $user->products()->where('product_id', $product->id)->sum('amount');
            abort_if(($user_amount + $amount) > $product->user_max_amount, 400, Lang('投资金额已达上限'));
        }

        //判断产品总规模
        if ($product->all_amount > 0) {
            $buy_amount = $product->userBuys()->where('product_id', $product->id)->sum('amount');
            abort_if(($buy_amount + $amount) > $product->all_amount, 400, Lang('产品总规模已达上限'));
        }


        switch ($product->type) {
            case ProductType::balance:
                //钱包扣款
                $walletService->withdraw($user, $amount, WalletType::balance, WalletLogSlug::deposit, WalletLogType::WithdrawWalletToProduct, Product::class, $product->id, function (Wallet $wallet, WalletLog $walletLog) use ($amount, $product, $user) {

                    //钱包统计 订单投资数据
                    $wallet->walletCount()->increment('product_amount', $amount);
                    $wallet->walletCount()->increment('product_amount_all', $amount);

                    //创建产品投资记录
                    $this->createUserProduct($user, $product, $amount, $walletLog);


                });

                break;
            case ProductType::usdt:
                //钱包扣款
                $walletService->withdraw($user, $amount, WalletType::usdt, WalletLogSlug::deposit, WalletLogType::WithdrawWalletToProduct, Product::class, $product->id, function (Wallet $wallet, WalletLog $walletLog) use ($amount, $product, $user) {
                    //钱包统计 订单投资数据
                    $wallet->walletCount()->increment('product_usdt_amount', $amount);
                    $wallet->walletCount()->increment('product_usdt_amount_all', $amount);
                    //创建产品投资记录
                    $this->createUserProduct($user, $product, $amount, $walletLog);

                });
                break;
            default:
                abort(400, Lang('产品类型错误'));
                break;
        }
    }

    /**
     * @param User $user
     * @param Product $product
     * @param float $amount
     * @param WalletLog $walletLog
     * @return UserProduct|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
     */
    private function createUserProduct($user, $product, $amount, $walletLog)
    {
        $up = UserProduct::query()->create([
            'user_id' => $user->id,
            'channel_id' => $user->channel_id,
            'link_id' => $user->link_id,
            'product_id' => $product->id,
            'product_type' => $product->type,
            'day_cycle' => $product->day_cycle,
            'day_rate' => $product->day_rate,
            'is_day_account' => $product->is_day_account,
            'last_grant_time' => now(),
            'next_grant_time' => $product->is_day_account ? Carbon::now()->addDays(1) : Carbon::now()->addDays($product->day_cycle),
            'amount' => $amount,
            'min_money' => $product->min_money,
            'over_day' => Carbon::now()->addDays($product->day_cycle),
            'buy_log_id' => $walletLog->id,
        ]);
        //触发购买产品钩子
        UserHookService::make()->buyProductHook($user, $up);

        return $up;
    }

    /**
     * 每日都分利息的产品分利息
     */
    public function checkProductGrantInterest()
    {
        UserProduct::query()
            ->where('is_day_account', 1)
            ->where('status', 1)
            ->where('next_grant_time', '<=', Carbon::now())//下一次结算时间小于等于现在
            ->where('is_over', 0)
            ->chunkById(30, function ($list) {
                \Log::info("处理每日结算产品：" . count($list));
                /** @var UserProduct $userProduct */
                foreach ($list as $userProduct) {
                    //直接处理
                    $this->productGrantInterest($userProduct);
                    //队列处理
                    //dispatch(new ProductGrantInterest($userProduct))->onQueue(QueueType::product);
                }
            });
    }

    public function checkDayProductOver()
    {
        UserProduct::query()
            ->where('is_day_account', 1)
            ->where('status', 1)
            ->where('over_day', '<=', Carbon::now()->addMinutes(1))//下一次结算时间小于等于现在
            ->where('is_over', 0)
            ->chunkById(30, function ($list) {
                /** @var UserProduct $userProduct */
                foreach ($list as $userProduct) {
                    if ($userProduct->day_cycle === $userProduct->interest_count) {
                        //直接处理
                        $this->productOver($userProduct);
                        //队列处理
                        //dispatch(new ProductOver($userProduct))->onQueue(QueueType::product);
                    }

                }
            });
    }

    /**
     * 每日结算产品利息结算逻辑
     * @param UserProduct|Builder $userProduct
     */
    public function productGrantInterest(UserProduct $userProduct)
    {
        try {
            if (!$userProduct->is_over && $userProduct->is_day_account && $userProduct->status && Carbon::make($userProduct->next_grant_time)->lte(Carbon::now())) {
                if ($userProduct->interest_count >= $userProduct->day_cycle) {
                    return;
                }
                $walletService = new WalletService();
                //计算日利息
                $fee = $userProduct->amount * ($userProduct->day_rate / 100);
                $user = $userProduct->user;
                switch ($userProduct->product_type) {
                    case ProductType::balance:
                        $walletService->deposit($user, $fee, WalletType::balance, WalletLogSlug::interest, WalletLogType::DepositProductInterestByBalance, UserProduct::class, $userProduct->id, function (Wallet $wallet, WalletLog $walletLog) use ($fee, $userProduct) {
                            $userProduct->increment('interest', $fee);
                            $userProduct->increment('interest_count');
                            $userProduct->last_grant_time = now();
                            $userProduct->next_grant_time = now()->addDays(1);
                            $userProduct->save();
                            //
                            $wallet->walletCount()->increment('product_balance_interest', $fee);

                        });
                        //判断产品是否需要结算
                        if (Carbon::make($userProduct->over_day)->lte(Carbon::now())) {
                            $this->productOver($userProduct);
                        }


                        break;
                    case ProductType::usdt:
                        $walletService->deposit($user, $fee, WalletType::usdt, WalletLogSlug::interest, WalletLogType::DepositProductInterestByUSDT, UserProduct::class, $userProduct->id, function (Wallet $wallet, WalletLog $walletLog) use ($fee, $userProduct) {
                            $userProduct->increment('interest', $fee);
                            $userProduct->increment('interest_count');
                            $userProduct->last_grant_time = now();
                            $userProduct->next_grant_time = now()->addDays(1);
                            $userProduct->save();
                            $wallet->walletCount()->increment('product_usdt_balance_interest', $fee);

                        });
                        //判断产品是否需要结算
                        if (Carbon::make($userProduct->over_day)->lte(Carbon::now())) {
                            $this->productOver($userProduct);
                        }

                        break;
                }
            }
        } catch (\Exception $exception) {
            \Log::error("定期投资产品每日利息发放错误:" . $exception->getMessage(), $userProduct->toArray());
        }
    }

    /**
     * 一次性产品结算
     */
    public function checkProductOver()
    {
        UserProduct::query()
            ->where('is_day_account', 0)//非每日结算
            ->where('status', 1)//状态
            ->where('over_day', '<=', Carbon::now())//当前时间大于订单结束时间
            ->where('is_over', 0)//没结束
            ->chunkById(30, function ($list) {
                \Log::info("处理一次性产品结算：" . count($list));
                /** @var UserProduct $userProduct */
                foreach ($list as $userProduct) {
                    //直接处理
                    $this->productOver($userProduct);
                    //队列处理
                    //dispatch(new ProductOver($userProduct))->onQueue(QueueType::product);
                }
            });
    }

    /**
     * 产品结算逻辑
     * @param UserProduct|Builder $userProduct
     */
    public function productOver($userProduct)
    {
        try {
            if (!$userProduct->is_over && $userProduct->status && Carbon::make($userProduct->over_day)->lte(Carbon::now())) {
                $walletService = new WalletService();
                $user = $userProduct->user;
                //非每日结算类型，先结算利息
                if (!$userProduct->is_day_account) {
                    //计算总利息
                    $fee = $userProduct->amount * ($userProduct->day_rate / 100) * $userProduct->day_cycle;

                    switch ($userProduct->product_type) {
                        case ProductType::balance:
                            $walletService->deposit($user, $fee, WalletType::balance, WalletLogSlug::interest, WalletLogType::DepositProductInterestByBalance, UserProduct::class, $userProduct->id, function (Wallet $wallet, WalletLog $walletLog) use ($fee, $userProduct) {
                                $userProduct->increment('interest', $fee);
                                $userProduct->increment('interest_count');
                                $userProduct->last_grant_time = now();
                                $userProduct->save();

                                $wallet->walletCount()->increment('product_balance_interest', $fee);
                            });
                            //TODO 利息发放成功

                            break;
                        case ProductType::usdt:
                            $walletService->deposit($user, $fee, WalletType::usdt, WalletLogSlug::interest, WalletLogType::DepositProductInterestByUSDT, UserProduct::class, $userProduct->id, function (Wallet $wallet, WalletLog $walletLog) use ($fee, $userProduct) {
                                $userProduct->increment('interest', $fee);
                                $userProduct->increment('interest_count');
                                $userProduct->last_grant_time = now();
                                $userProduct->save();

                                $wallet->walletCount()->increment('product_usdt_balance_interest', $fee);

                            });
                            //TODO 利息发放成功

                            break;
                    }
                }
                //退换本金到余额

                $amount = $userProduct->amount;

                switch ($userProduct->product_type) {
                    case ProductType::balance:
                        $walletService->deposit($user, $amount, WalletType::balance, WalletLogSlug::takeOut, WalletLogType::DepositProductToWallet, UserProduct::class, $userProduct->id, function (Wallet $wallet, WalletLog $walletLog) use ($amount, $userProduct) {

                            //钱包统计扣除当前投资总额
                            $wallet->walletCount()->decrement('product_amount', $amount);

                            $userProduct->is_over = true;
                            $userProduct->over_time = now();
                            $userProduct->over_log_id = $walletLog->id;
                            $userProduct->save();
                        });
                        //TODO 本金退换成功
                        $user->notify(new UserProductOverNotification($userProduct));
                        break;
                    case ProductType::usdt:
                        $walletService->deposit($user, $amount, WalletType::usdt, WalletLogSlug::takeOut, WalletLogType::DepositProductToWallet, UserProduct::class, $userProduct->id, function (Wallet $wallet, WalletLog $walletLog) use ($amount, $userProduct) {

                            //钱包统计扣除当前投资总额
                            $wallet->walletCount()->decrement('product_usdt_amount', $amount);

                            $userProduct->is_over = true;
                            $userProduct->over_time = now();
                            $userProduct->over_log_id = $walletLog->id;
                            $userProduct->save();
                        });
                        //TODO 本金退换成功
                        $user->notify(new UserProductOverNotification($userProduct));
                        break;
                }

            }
        } catch (\Exception $exception) {
            \Log::error("定期投资产品结算错误:" . $exception->getMessage(), $userProduct->toArray());
        }

    }

    public function commissionHandle(User $user, UserProduct $userProduct)
    {
        $product = $userProduct->product;

        $walletService = new WalletService();
        //产品分佣
        if ($product->is_commission) {
            //当前产品总收益
            $fee = $userProduct->amount * ($userProduct->day_rate / 100) * $userProduct->day_cycle;
            if ($fee > 0) {
                //获取用户上级
                $user_invite = $user->invite;
                $userInviteAward = UserInviteAward::query()->firstOrCreate(['user_id' => $user->id], [
                    'channel_id' => $user->channel_id,
                    'link_id' => $user->link_id,
                ]);
                for ($i = 1; $i <= 10; $i++) {
                    $fee = $userProduct->amount * ($userProduct->day_rate / 100) * $userProduct->day_cycle;
                    $p_fee = 0;
                    //上级ID
                    $invite_id = data_get($user_invite, 'invite_id_' . $i, 0);
                    //当前等级奖励比例
                    $p_rate = (float)data_get($product->commission_config, "parent_" . $i . "_rate", 0);
                    if ($invite_id <= 0 || $p_rate <= 0) continue;

                    //需要加款的用户
                    $invite_user = User::query()->find($invite_id);
                    if (!$invite_user->status) continue;


                    //判断用户是当前持有产品总额
                    $is_no_commission = false;
                    $is_buy_product = false;
                    $p_day = 100;

                    $invite_user_zhu_product_amount = $invite_user->products()
                        ->where('day_cycle', '<=', $p_day)
                        ->where('is_over', 0)
                        ->sum('amount');

                    $invite_user_buy_product_amount = $invite_user->products()
                        ->where('day_cycle', '>', $p_day)
                        ->where('is_over', 0)
                        ->sum('amount');

                    if ($userProduct->day_cycle <= $p_day) {
                        $invite_user_product_amount = $invite_user_zhu_product_amount;
                    } else {
                        $is_buy_product = true;
                        $invite_user_product_amount = $invite_user_buy_product_amount;
                    }

                    //是否得到全部佣金
                    $is_get_all_commission = true;
                    //等级总佣金
                    $all_fee = round($fee * ($p_rate / 100), 8);

                    //用户未持有当前类型矿机
                    if ($invite_user_product_amount <= 0) {
                        $is_no_commission = true;
                        $invite_user->notify(new UserProductCommissionV2Notification($p_fee, $all_fee, $is_no_commission, $is_get_all_commission, $is_buy_product, $i, $user, $userProduct, $invite_user_buy_product_amount, $invite_user_zhu_product_amount));
                        continue;
                    }


                    if ($invite_user_product_amount >= $userProduct->amount) {
                        $fee = $userProduct->amount * ($userProduct->day_rate / 100) * $userProduct->day_cycle;
                    } else {
                        $fee = $invite_user_product_amount * ($userProduct->day_rate / 100) * $userProduct->day_cycle;
                        $is_get_all_commission = false;
                    }

                    //当前用户佣金
                    $p_fee = round($fee * ($p_rate / 100), 8);
                    if ($p_fee <= 0) continue;

                    //佣金入账
                    $wallet_type = WalletType::balance;
                    if ($product->type == ProductType::usdt) $wallet_type = WalletType::usdt;
                    $walletService->deposit($invite_user, $p_fee, $wallet_type,
                        WalletLogSlug::commission,
                        WalletLogType::DepositFriendBuyProductCommission,
                        UserProduct::class, $userProduct->id, function (Wallet $wallet, WalletLog $walletLog) {

                        });
                    //佣金发放成功
                    $i_userInviteAward = UserInviteAward::query()->firstOrCreate(['user_id' => $invite_user->id], [
                        'channel_id' => $invite_user->channel_id,
                        'link_id' => $invite_user->link_id,
                    ]);
                    //上级统计总数据
                    $i_userInviteAward->increment('all_commission', $p_fee);
                    //当前下级统计给上级产生的佣金
                    $userInviteAward->increment('p_' . $i . '_commission', $p_fee);
                    //发送佣金通知
                    if ($is_get_all_commission) {
                        $invite_user->notify(new UserProductCommissionNotification($p_fee, $i, $userProduct));
                    } else {
                        $invite_user->notify(new UserProductCommissionV2Notification($p_fee, $all_fee, $is_no_commission, false, $is_buy_product, $i, $user, $userProduct, $invite_user_buy_product_amount, $invite_user_zhu_product_amount));
                    }


                }
            }

        }
    }

}
