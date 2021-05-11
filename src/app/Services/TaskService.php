<?php


namespace App\Services;


use App\Enums\TaskTargetType;
use App\Enums\UserHookType;
use App\Enums\WalletLogSlug;
use App\Enums\WalletLogType;
use App\Enums\WalletType;
use App\Enums\WithdrawOrderStatusType;
use App\Models\Notifications\UserAwardNotification;
use App\Models\Task;
use App\Models\User;
use App\Models\UserAwardRecord;
use App\Models\UserInviteAward;
use App\Models\UserRechargeOrder;
use App\Models\UserTask;
use App\Models\UserTaskLog;
use App\Models\Wallet;
use App\Models\WalletLog;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Psy\Util\Str;

class TaskService extends BaseService
{


    /**
     * 获取用户未完成任务列表
     * @param $user
     */
    public function getUserUndoneTasks($user)
    {

    }

    public function getTaskOrm()
    {
        return Task::query()
            ->where('status', true)
            ->where(function ($q) {
                /**@var Builder $q */
                $q->whereNull('end_time')->orWhereDate('end_time', '>', now());
            })->where(function ($q) {
                /**@var Builder $q */
                $q->whereNull('start_time')->orWhereDate('start_time', '<=', now());
            });
    }

    /**
     * @param $hook
     * @return Task|\Illuminate\Database\Eloquent\Builder
     */
    public function getTaskByHookOrm($hook)
    {
        return $this->getTaskOrm()->where('hook', $hook);
    }

    /**
     * 注册钩子处理
     * @param User $user
     */
    public function registerHookHandle(User $user)
    {
        $orm = $this->getTaskByHookOrm(UserHookType::Register)->with(['userTask' => fn($q) => $q->where('user_id', $user->id)]);
        $list = $orm->get();
        foreach ($list as $task) {
            $userTask = $this->getTaskIsAchieve($task, $user);
            if ($userTask) {
                $this->progressUserTask($user, $task, $userTask);
            }
        }
    }

    /**
     * 签到事件钩子处理
     * @param User $user
     */
    public function signHookHandle(User $user)
    {

        $orm = $this->getTaskByHookOrm(UserHookType::Sign)->with(['userTask' => fn($q) => $q->where('user_id', $user->id)]);
        $list = $orm->get();
        foreach ($list as $task) {
            $userTask = $this->getTaskIsAchieve($task, $user);
            if ($userTask) {
                $this->progressUserTask($user, $task, $userTask);
            }

        }
    }


    /**
     * 邀请事件钩子处理
     * @param int $user_id 被邀请用户ID
     * @param int $invite_user_id 邀请人用户ID
     * @param int $level 邀请等级
     */
    public function inviteHookHandle($user_id, int $invite_user_id, int $level)
    {

        $son_user = User::query()->find($user_id);
        $user = User::query()->find($invite_user_id);
        //判读上下级关系是否对等
        if ($son_user->invite_id !== $user->id) return;

        $orm = $this->getTaskByHookOrm(UserHookType::Invite)->with(['userTask' => fn($q) => $q->where('user_id', $user->id)]);
        $list = $orm->get();
        foreach ($list as $task) {
            $userTask = $this->getTaskIsAchieve($task, $user);
            if ($userTask) {
                $this->progressUserTask($user, $task, $userTask);
            }
        }
    }

    /**
     * 充值事件钩子处理
     * @param User $user
     * @param UserRechargeOrder $userRechargeOrder
     */
    public function rechargeHookHandle(User $user, UserRechargeOrder $userRechargeOrder)
    {
        $orm = $this->getTaskByHookOrm(UserHookType::Recharge)->with(['userTask' => fn($q) => $q->where('user_id', $user->id)]);
        $list = $orm->get();
        foreach ($list as $task) {
            $userTask = $this->getTaskIsAchieve($task, $user);
            if ($userTask) {
                $this->progressUserTask($user, $task, $userTask, $userRechargeOrder);
            }
        }
    }

    /**
     * 利息收益发放钩子处理
     * @param User $user
     */
    public function earningsHookHandle(User $user, WalletLog $walletLog)
    {
        $orm = $this->getTaskByHookOrm(UserHookType::Earnings)->with(['userTask' => fn($q) => $q->where('user_id', $user->id)]);
        $list = $orm->get();
        foreach ($list as $task) {
            $userTask = $this->getTaskIsAchieve($task, $user);
            if ($userTask) {
                $this->progressUserTask($user, $task, $userTask, $walletLog);
            }
        }
    }


    /**
     * 完成用户任务进度
     * @param User|Builder $user
     * @param Task|Builder $task
     * @param UserTask|Builder $userTask
     * @param UserRechargeOrder|WalletLog $hookData
     */
    private function progressUserTask($user, Task $task, UserTask $userTask, $hookData = null)
    {

        switch ($task->task_target) {
            //首次
            case TaskTargetType::First:
                //注册
                if ($task->hook == UserHookType::Register) {
                    //完成任务
                    $this->achieveUserTask($user, $task, $userTask, WalletLogType::DepositRegisterAward);
                }
                //充值
                if ($task->hook == UserHookType::Recharge) {

                    //判断充值金额是否达到目标条件
                    $am = $hookData->amount;
                    if ($hookData->wallet_type == WalletType::usdt) {
                        $am = $hookData->amount * (float)Setting('usdt_money_rate');
                    }

                    if ($task->target_condition > 0 && $am < $task->target_condition) break;

                    //完成任务
                    $this->achieveUserTask($user, $task, $userTask, WalletLogType::DepositFirstRechargeAward, $hookData);
                }
                break;
            //每次
            case TaskTargetType::Every:
                //完成每日签到
                if ($task->hook == UserHookType::Sign) {
                    //完成任务
                    $this->achieveUserTask($user, $task, $userTask, WalletLogType::DepositDaySignAward);

                }
                //每邀请一位好友
                if ($task->hook == UserHookType::Invite) {
                    //完成任务
                    $this->achieveUserTask($user, $task, $userTask, WalletLogType::DepositEveryInviteAward);
                }
                //每次充值
                if ($task->hook == UserHookType::Recharge) {
                    $this->achieveUserTask($user, $task, $userTask, WalletLogType::DepositEveryRechargeAward, $hookData);
                }
                break;
            //累计
            case TaskTargetType::Accomplish:
                //累计签到
                if ($task->hook == UserHookType::Sign) {
                    $userTask->increment('target_condition'); //目标条件 +1次签到
                    //满足任务目标
                    if ($userTask->target_condition >= $task->target_condition) {
                        $this->achieveUserTask($user, $task, $userTask, WalletLogType::DepositTotalSignAward);
                    } else {
                        $userTask->last_time = now();//最后一次完成时间
                        $userTask->next_time = Carbon::today()->addDays(1);//下一次可以完成的日期
                        $userTask->save();
                    }
                }
                //累计邀请
                if ($task->hook == UserHookType::Invite) {
                    $userTask->increment('target_condition'); //目标条件 +1次邀请
                    //满足任务目标
                    if ($userTask->target_condition >= $task->target_condition) {
                        $this->achieveUserTask($user, $task, $userTask, WalletLogType::DepositTotalInviteAward);
                    } else {
                        $userTask->last_time = now();//最后一次完成时间
                        $userTask->next_time = Carbon::today()->addDays(1);//下一次可以完成的日期
                        $userTask->save();
                    }
                }
                //累计充值
                if ($task->hook == UserHookType::Recharge) {

                    //获取充值金额
                    $amount = (float)$hookData->amount;
                    if ($hookData->wallet_type == WalletType::usdt) $amount = $amount * (float)Setting('usdt_money_rate');


                    //计算目标条件数值
                    $check_condition = $userTask->target_condition + $amount;
                    if ($check_condition > 0) {
                        $userTask->target_condition = $check_condition;
                    } else {
                        $userTask->target_condition = 0;
                    }

                    //判断提现是否参与
                    $all_withdraw = 0;
                    if ($task->check_withdraw) {
                        $balance_withdraw = (float)$user->withdrawOrders()
                            ->where('wallet_type', WalletType::balance)
                            ->whereIn('order_status', [WithdrawOrderStatusType::CheckSuccess, WithdrawOrderStatusType::Checking, WithdrawOrderStatusType::Paying])
                            ->sum('actual_amount');
                        $usdt_balance_withdraw = (float)$user->withdrawOrders()
                            ->where('wallet_type', WalletType::usdt)
                            ->whereIn('order_status', [WithdrawOrderStatusType::CheckSuccess, WithdrawOrderStatusType::Checking, WithdrawOrderStatusType::Paying])
                            ->sum('actual_amount');
                        //用户总提现金额
                        $all_withdraw = $balance_withdraw + ($usdt_balance_withdraw * (float)Setting('usdt_money_rate'));
                    }
                    $target_condition = $userTask->target_condition - $all_withdraw;

                    //满足任务目标
                    if ($target_condition >= $task->target_condition) {
                        $this->achieveUserTask($user, $task, $userTask, WalletLogType::DepositTotalRechargeAward, $hookData);
                    } else {
                        $userTask->last_time = now();//最后一次完成时间
                        $userTask->next_time = Carbon::today()->addDays(1);//下一次可以完成的日期
                        $userTask->save();
                    }

                }
                //累计收益
                if ($task->hook == UserHookType::Earnings) {
                    //计算收益
                    $fee = $hookData->fee;
                    if ($hookData->wallet_type == WalletType::usdt) $fee = $hookData->fee * (float)Setting('usdt_money_rate');
                    $userTask->increment('target_condition', $fee);
                    //满足任务目标
                    if ($userTask->target_condition >= $task->target_condition) {
                        $this->achieveUserTask($user, $task, $userTask, WalletLogType::DepositTotalEarningsAward);
                    } else {
                        $userTask->last_time = now();//最后一次完成时间
                        $userTask->next_time = Carbon::today()->addDays(1);//下一次可以完成的日期
                        $userTask->save();
                    }
                }
                break;
            //连续N天
            case TaskTargetType::ContinuousDay:
                //连续签到
                if ($task->hook == UserHookType::Sign) {
                    //判断昨日是否完成，最后一次完成时间在昨天
                    if (empty($userTask->last_time) || Carbon::make($userTask->last_time)->between(Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay())) {
                        $userTask->increment('ut_continuous_day');//连续天数 + 1
                        $userTask->increment('target_condition'); //目标条件 +1次签到
                    } else {
                        //昨天未完成任务，连续天数重新设置为1
                        $userTask->ut_continuous_day = 1;
                    }
                    //已经达到天数要求
                    if ($userTask->ut_continuous_day >= $task->continuous_day) {
                        $this->achieveUserTask($user, $task, $userTask, WalletLogType::DepositContinuousSignAward);
                    } else {
                        $userTask->last_time = now();//最后一次完成时间
                        $userTask->next_time = Carbon::today()->addDays(1);//下一次需要完成的日期
                        $userTask->save();
                    }
                }
                //连续邀请
                if ($task->hook == UserHookType::Invite) {
                    //判读昨日阶段目标是否完成
                    if (empty($userTask->last_achieve_time) || !Carbon::make($userTask->last_achieve_time)->between(Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay())) {
                        //昨日未完成，重新开始
                        //连续天数设置为0
                        $userTask->ut_continuous_day = 0;
                        //如果今日没有触发过任务
                        if (empty($userTask->last_time) || Carbon::make($userTask->last_time)->lt(Carbon::today())) {
                            //目标设置0
                            $userTask->target_condition = 0;
                        }

                        $userTask->save();
                    }
                    //计算今日目标
                    $userTask->increment('target_condition'); //目标条件 +1次邀请
                    //计算今日是否完成目标
                    if ($userTask->target_condition >= $task->target_condition) {
                        //今日目标已完成
                        $userTask->increment('ut_continuous_day');//连续天数 + 1
                        $userTask->last_achieve_time = now();//更新最后一次完成每天目标时间
                        $userTask->target_condition = 0;//归零目标条件完成数
                        $userTask->last_time = now();//最后一次完成时间
                        $userTask->next_time = Carbon::today()->addDays(1);//下一次需要完成的日期
                        $userTask->save();
                        //计算总目标是否已完成，连续天数满足设定天数
                        if ($userTask->ut_continuous_day >= $task->continuous_day) {
                            $this->achieveUserTask($user, $task, $userTask, WalletLogType::DepositContinuousInviteAward);
                        }

                    } else {
                        //今日小目标未完成
                        $userTask->last_time = now();//最后一次触发时间
                        $userTask->next_time = Carbon::today()->addDays(1);//下一次需要完成的日期
                        $userTask->save();
                    }

                }
                //连续充值
                if ($task->hook == UserHookType::Recharge) {
                    //判读昨日阶段目标是否完成
                    if (empty($userTask->last_achieve_time) || !Carbon::make($userTask->last_achieve_time)->between(Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay())) {
                        //昨日未完成，重新开始
                        //连续天数设置为
                        $userTask->ut_continuous_day = 0;
                        //如果今日没有触发过任务
                        if (empty($userTask->last_time) || Carbon::make($userTask->last_time)->lt(Carbon::today())) {
                            //目标设置0
                            $userTask->target_condition = 0;
                        }
                        $userTask->save();
                    }
                    //获取充值金额
                    $amount = (float)$hookData->amount;
                    if ($hookData->wallet_type == WalletType::usdt) $amount = $amount * (float)Setting('usdt_money_rate');

                    //计算目标条件数值
                    $check_condition = $userTask->target_condition + $amount;
                    if ($check_condition > 0) {
                        $userTask->target_condition = $check_condition;
                    } else {
                        $userTask->target_condition = 0;
                    }

                    //判断提现是否参与
                    $all_withdraw = 0;
                    if ($task->check_withdraw) {
                        $balance_withdraw = (float)$user->withdrawOrders()
                            ->where('created_at', '>=', Carbon::today())
                            ->where('wallet_type', WalletType::balance)
                            ->whereIn('order_status', [WithdrawOrderStatusType::CheckSuccess, WithdrawOrderStatusType::Checking, WithdrawOrderStatusType::Paying])
                            ->sum('actual_amount');
                        $usdt_balance_withdraw = (float)$user->withdrawOrders()
                            ->where('created_at', '>=', Carbon::today())
                            ->where('wallet_type', WalletType::usdt)
                            ->whereIn('order_status', [WithdrawOrderStatusType::CheckSuccess, WithdrawOrderStatusType::Checking, WithdrawOrderStatusType::Paying])
                            ->sum('actual_amount');
                        //用户总提现金额
                        $all_withdraw = $balance_withdraw + ($usdt_balance_withdraw * (float)Setting('usdt_money_rate'));
                    }

                    $target_condition = $userTask->target_condition - $all_withdraw;


                    //计算今日是否完成目标
                    if ($target_condition >= $task->target_condition) {
                        //今日目标已完成
                        $userTask->increment('ut_continuous_day');//连续天数 + 1
                        $userTask->last_achieve_time = now();//更新最后一次完成每天目标时间
                        $userTask->target_condition = 0;//归零目标条件完成数
                        $userTask->last_time = now();//最后一次完成时间
                        $userTask->next_time = Carbon::today()->addDays(1);//下一次需要完成的日期
                        $userTask->save();
                        //计算总目标是否已完成，连续天数满足设定天数
                        if ($userTask->ut_continuous_day >= $task->continuous_day) {
                            $this->achieveUserTask($user, $task, $userTask, WalletLogType::DepositContinuousRechargeAward, $hookData);
                        }
                    } else {
                        //今日小目标未完成
                        $userTask->last_time = now();//最后一次触发时间
                        $userTask->next_time = Carbon::today()->addDays(1);//下一次需要完成的日期
                        $userTask->save();
                    }
                }
                //连续收益
                if ($task->hook == UserHookType::Earnings) {
                    //判读昨日阶段目标是否完成
                    if (empty($userTask->last_achieve_time) || !Carbon::make($userTask->last_achieve_time)->between(Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay())) {
                        //昨日未完成
                        //连续天数设置为
                        $userTask->ut_continuous_day = 0;
                        //如果今日没有触发过任务
                        if (empty($userTask->last_time) || Carbon::make($userTask->last_time)->lt(Carbon::today())) {
                            //目标设置0
                            $userTask->target_condition = 0;
                        }
                        $userTask->save();
                    }
                    //计算今日目标
                    //计算收益
                    $fee = $hookData->fee;

                    if ($hookData->wallet_type == WalletType::usdt) $fee = $hookData->fee * (float)Setting('usdt_money_rate');

                    $userTask->increment('target_condition', $fee);

                    //计算今日是否完成目标
                    if ($userTask->target_condition >= $task->target_condition) {
                        //今日目标已完成
                        $userTask->increment('ut_continuous_day');//连续天数 + 1
                        $userTask->last_achieve_time = now();//更新最后一次完成每天目标时间
                        $userTask->target_condition = 0;//归零目标条件完成数
                        $userTask->last_time = now();//最后一次完成时间
                        $userTask->next_time = Carbon::today()->addDays(1);//下一次需要完成的日期
                        $userTask->save();
                        //计算总目标是否已完成，连续天数满足设定天数
                        if ($userTask->ut_continuous_day >= $task->continuous_day) {
                            $this->achieveUserTask($user, $task, $userTask, WalletLogType::DepositContinuousEarningsAward);
                        }

                    } else {
                        //今日小目标未完成
                        $userTask->last_time = now();//最后一次触发时间
                        $userTask->next_time = Carbon::today()->addDays(1);//下一次需要完成的日期
                        $userTask->save();
                    }
                }
                break;
            //连续N天递增
            case TaskTargetType::ContinuousDayIncrease:
                //连续递增充值
                if ($task->hook == UserHookType::Recharge) {
                    //判读昨日阶段目标是否完成
                    if (empty($userTask->last_achieve_time) || !Carbon::make($userTask->last_achieve_time)->between(Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay())) {
                        //昨日未完成
                        //连续天数设置为
                        $userTask->ut_continuous_day = 0;
                        //如果今日没有触发过任务
                        if (empty($userTask->last_time) || Carbon::make($userTask->last_time)->lt(Carbon::today())) {
                            //目标设置0
                            $userTask->target_condition = 0;
                        }
                        $userTask->save();
                    }
                    //获取充值金额
                    $amount = (float)$hookData->amount;
                    if ($hookData->wallet_type == WalletType::usdt) $amount = $amount * (float)Setting('usdt_money_rate');

                    //计算目标条件数值
                    $check_condition = $userTask->target_condition + $amount;
                    if ($check_condition > 0) {
                        $userTask->target_condition = $check_condition;
                    } else {
                        $userTask->target_condition = 0;
                    }
                    //今日达标金额 = 初始金额 + 当前天数*递增金额
                    //初始金额
                    $start_amount = (float)$task->start_amount;
                    //每天递增金额
                    $increase_amount = (float)$task->increase_amount;

                    $day_target_condition = $start_amount + $increase_amount * $userTask->ut_continuous_day;


                    //判断提现是否参与
                    $all_withdraw = 0;
                    if ($task->check_withdraw) {
                        $balance_withdraw = (float)$user->withdrawOrders()
                            ->where('created_at', '>=', Carbon::today())
                            ->where('wallet_type', WalletType::balance)
                            ->whereIn('order_status', [WithdrawOrderStatusType::CheckSuccess, WithdrawOrderStatusType::Checking, WithdrawOrderStatusType::Paying])
                            ->sum('actual_amount');
                        $usdt_balance_withdraw = (float)$user->withdrawOrders()
                            ->where('created_at', '>=', Carbon::today())
                            ->where('wallet_type', WalletType::usdt)
                            ->whereIn('order_status', [WithdrawOrderStatusType::CheckSuccess, WithdrawOrderStatusType::Checking, WithdrawOrderStatusType::Paying])
                            ->sum('actual_amount');
                        //用户总提现金额
                        $all_withdraw = $balance_withdraw + ($usdt_balance_withdraw * (float)Setting('usdt_money_rate'));
                    }

                    $target_condition = $userTask->target_condition - $all_withdraw;

                    //计算今日是否完成目标
                    if ($target_condition >= $day_target_condition) {
                        //今日目标已完成
                        $userTask->increment('ut_continuous_day');//连续天数 + 1
                        $userTask->last_achieve_time = now();//更新最后一次完成每天目标时间
                        $userTask->target_condition = 0;//归零目标条件完成数
                        $userTask->last_time = now();//最后一次完成时间
                        $userTask->next_time = Carbon::today()->addDays(1);//下一次需要完成的日期
                        $userTask->save();
                        //计算总目标是否已完成，连续天数满足设定天数
                        if ($userTask->ut_continuous_day >= $task->continuous_day) {
                            $this->achieveUserTask($user, $task, $userTask, WalletLogType::DepositContinuousIncreaseRechargeAward, $hookData);
                        }
                    } else {
                        //今日小目标未完成
                        $userTask->last_time = now();//最后一次触发时间
                        $userTask->next_time = Carbon::today()->addDays(1);//下一次需要完成的日期
                        $userTask->save();
                    }
                }
                break;
        }

    }

    /**
     * 完成任务
     * @param User|Builder $user
     * @param Task|Builder $task
     * @param UserTask|Builder $userTask
     * @param $action_type
     * @param UserRechargeOrder $hookData
     */
    private function achieveUserTask(User $user, Task $task, UserTask $userTask, $action_type, $hookData = null)
    {

        $walletService = new WalletService();

        $wallet_type = WalletType::give;

        if ($task->wallet_type == WalletType::balance) {
            $wallet_type = WalletType::balance;
        }

        //给用户发放奖励
        if ($task->is_user_award) {
            $fee = (float)$task->user_award_amount;
            //计算奖励金额

            $rate = (float)$task->deduct_rate;

            //如果是充值事件，并且是按金额比例奖励
            if ($task->hook == UserHookType::Recharge && $task->user_award_rate > 0 && $hookData) {

                $rate = (float)$task->user_award_rate;

                if ($hookData->wallet_type == WalletType::balance) {
                    $fee = (float)$hookData->amount * ($rate / 100);
                }
                if ($hookData->wallet_type == WalletType::usdt) {
                    $fee = (float)$hookData->amount * (float)Setting('usdt_money_rate') * ($rate / 100);
                }

            }


            if ($fee > 0) {


                $walletService->deposit($user, $fee, $wallet_type, WalletLogSlug::award, $action_type, UserTask::class, $userTask->id, function (Wallet $wallet, WalletLog $walletLog) use ($action_type, $fee, $rate, $user, $task, $userTask) {

                    $userTask->achieve = true;
                    $userTask->achieve_time = now();
                    $userTask->last_time = now();//最后一次完成时间
                    $userTask->next_time = Carbon::today()->addDays(1);//下一次需要完成的日期
                    $userTask->achieve = true;//标记已完成
                    $userTask->wallet_log_id = $walletLog->id;//奖励流水
                    $userTask->repetition = $task->repetition;
                    $userTask->increment('achieve_count');
                    $userTask->save();
                    $data = $userTask->toArray();
                    $data['user_task_id'] = $data['id'];
                    unset($data['id']);
                    UserTaskLog::query()->create($data);
                });

                //允许重复
                if ($task->repetition) {
                    //初始化任务记录
                    $this->resetUserTask($userTask);
                }

                //写入除奖励记录
                UserAwardRecord::query()->create([
                    'user_id' => $user->id,//谁获得奖励
                    'son_user_id' => $user->id,//谁给产生的奖励
                    'level' => 0,//关系层级，0就是自己
                    'rate' => (float)$rate,//赠送比例/扣除比例
                    'amount' => $fee,//奖励金额
                    'wallet_type' => $wallet_type,
                    'residue_amount' => $fee,//剩余可扣金额
                    'deduct_count' => 0,//扣除次数
                    'task_id' => $task->id,
                    'is_deduct' => (bool)$task->is_deduct,//是否可以扣除
                    'user_task_id' => $userTask->id,
                ]);


                //发送奖励到账通知
                $user->notify(new UserAwardNotification($fee, $action_type, $task));
            }
        }
        //如果需要给上级奖励
        if ($task->is_parent_award) {
            $userInviteAward = UserInviteAward::query()->firstOrCreate(['user_id' => $user->id], [
                'channel_id' => $user->channel_id,
                'link_id' => $user->link_id,
            ]);
            for ($i = 1; $i <= 10; $i++) {
                /*******************************/
                //上级ID
                $invite_id = data_get($user->invite, 'invite_id_' . $i, 0);
                //当前等级奖励比例
                $p_rate = data_get($task, 'parent_award_rate_' . $i);
                //有上级，有奖励比例
                if ($invite_id > 0 && $p_rate > 0) {
                    //计算比例
                    $i_rate = $p_rate / 100;
                    //计算固定奖励的金额
                    $i_fee = (float)$task->user_award_amount * $i_rate;

                    //如果是充值事件，并且是按金额比例奖励
                    if ($task->hook == UserHookType::Recharge && $task->user_award_rate > 0 && $hookData) {
                        if ($hookData->wallet_type == WalletType::balance) {
                            $i_fee = (float)$hookData->amount * $i_rate;
                        }
                        if ($hookData->wallet_type == WalletType::usdt) {
                            $i_fee = (float)$hookData->amount * (float)Setting('usdt_money_rate') * $i_rate;
                        }

                    }
                    if ($i_fee <= 0) continue;

                    //需要加款的用户
                    $invite_user = User::query()->find($invite_id);
                    //执行加款
                    $walletService->deposit($invite_user, $i_fee, $wallet_type, WalletLogSlug::award, WalletLogType::DepositFriendTaskAward, UserTask::class, $userTask->id, function (Wallet $wallet, WalletLog $walletLog) use ($i_fee, $invite_user, $task, $userTask) {


                    });
                    $i_userInviteAward = UserInviteAward::query()->firstOrCreate(['user_id' => $invite_user->id], [
                        'channel_id' => $invite_user->channel_id,
                        'link_id' => $invite_user->link_id,
                    ]);
                    //上级统计总数据
                    $i_userInviteAward->increment('give_balance', $i_fee);
                    $i_userInviteAward->increment('all_give_balance', $i_fee);

                    //当前下级统计给上级产生的赠送金
                    $userInviteAward->increment('p_' . $i . '_give_balance', $i_fee);

                    //可好友奖励记录
                    UserAwardRecord::query()->create([
                        'user_id' => $invite_id,//谁获得奖励
                        'son_user_id' => $user->id,//谁给产生的奖励
                        'level' => $i,//关系层级
                        'rate' => (float)$p_rate,//赠送比例/扣除比例 %
                        'amount' => $i_fee,//奖励金额
                        'wallet_type' => $wallet_type,
                        'residue_amount' => $i_fee,//剩余可扣金额
                        'deduct_count' => 0,//扣除次数
                        'task_id' => $task->id,
                        'is_deduct' => (bool)$task->is_deduct,//是否可以扣除
                        'user_task_id' => $userTask->id,
                    ]);


                    $invite_user->notify(new UserAwardNotification($i_fee, WalletLogType::DepositFriendTaskAward, $task));

                }
                /*******************************/
            }
        }


    }


    /**
     * 获取用户任务数据
     * @param Task $task
     */
    private function getTaskIsAchieve(Task $task, User $user)
    {
        //如果不存在数据
        if (!$task->userTask) {
            //创建并返回
            return $this->createUserTask($task, $user);
        }
        //未充值用户
        if ($user->recharge_count <= 0 && $task->user_type == 2) {
            return null;
        }

        //已充值用户
        if ($user->recharge_count > 0 && $task->user_type == 1) {
            return null;
        }


        //任务是否已标记完成
        $achieve = $task->userTask->achieve;
        //允许重复
        $repetition = $task->repetition;

        $check_last_time = true;


        //每天触发次数限制
        if (in_array($task->hook, [UserHookType::Invite, UserHookType::Recharge,])) {
            $check_last_time = false;

            if ($task->day_max > 0 && $task->task_target == TaskTargetType::Every) {
                //获取当前任务今日完成次数
                $count = UserTaskLog::query()->where('user_id', $user->id)->where('task_id', $task->id)
                    ->whereBetween('achieve_time', [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()])->count();
                if ($count >= $task->day_max) return null;
            }

        }
        //如果今天小目标已经完成
        if ($task->userTask->last_achieve_time && Carbon::make($task->userTask->last_achieve_time)->gt(Carbon::today())) {
            return null;
        }

        //如果是今天已经触发
        if ($check_last_time && $task->userTask->last_time && Carbon::make($task->userTask->last_time)->gt(Carbon::today())) {
            return null;
        }


        //任务已完成
        if ($achieve) {

            return null;
        } else {

            return $task->userTask;
        }
    }

    /**
     * 任务记录重新开始新的一轮
     * @param Task $task
     * @param UserTask|Builder $userTask
     * @param User $user
     */
    private function resetUserTask(UserTask $userTask)
    {

        $userTask->target_condition = 0;
        $userTask->achieve = false;

        $userTask->next_time = Carbon::today();

        $userTask->save();

    }

    /**
     * 创建用户任务记录
     * @param Task $task
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
     */
    private function createUserTask(Task $task, User $user)
    {
        return UserTask::query()->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'user_level' => $user->invite->level,
            'channel_id' => $user->channel_id,
            'link_id' => $user->link_id,
            'achieve' => false,//是否已完成
            'hook' => $task->hook,
            'task_target' => $task->task_target,
            'repetition' => (bool)$task->repetition,//是否允许重复
            'next_time' => Carbon::today(),//下一次需要完成的日期
            'wallet_log_id' => 0,//奖励流水ID

        ]);

    }

}
