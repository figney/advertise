<?php


namespace App\Services;


use App\Enums\QueueType;
use App\Enums\UserAdTaskType;
use App\Enums\WalletLogSlug;
use App\Enums\WalletLogType;
use App\Enums\WalletType;
use App\Jobs\SocketIoToUser;
use App\Models\AdTask;
use App\Models\Notifications\UserAdTaskCommissionNotification;
use App\Models\Notifications\UserAdTaskFinishedNotification;
use App\Models\User;
use App\Models\UserAdTask;
use App\Models\UserAdTaskLog;
use App\Models\UserInviteAward;
use App\Models\Wallet;
use App\Models\WalletLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class AdTaskService extends BaseService
{

    /**
     * 获取ORM
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getOrm()
    {
        return AdTask::query();
    }


    /**
     * 用户接取任务
     * @param User $user
     * @param AdTask|Builder $adTask
     */
    public function userReceiveAdTask(User $user, AdTask $adTask)
    {


        $this->checkAdTaskVipConfigByUser($user, $adTask);


        abort_if($this->checkUserAdTaskIsInProgress($user, $adTask), 400, Lang("存在未完成任务无法重复接取"));

        $task_num = (int)Setting('free_task_num', 1);
        if ($adTask->vip_level > 0) {
            $task_num = (int)VipService::make()->getUserVipLevelTaskNum($user, $adTask->vip_level);
        }


        abort_if($this->getUserTodayAdTaskCount($user, $adTask->vip_level) >= $task_num, 400, 10002);

        \DB::beginTransaction();


        $userAdTask = UserAdTask::query()->create([
            'user_id' => $user->id,
            'channel_id' => $user->channel_id,
            'link_id' => $user->link_id,
            'ad_task_id' => $adTask->id,
            'level' => $adTask->vip_level,
            'money' => $adTask->money,
            'complete_click_number' => $adTask->complete_click_number,
            'overdue_return' => $adTask->overdue_return,
            'now_click_number' => 0,
            'status' => UserAdTaskType::InProgress,
            'expired_time' => now()->addHours($adTask->valid_hour),
            'ip' => $this->getIP(),
            'imei' => $this->getIMEI(),
        ]);

        $adTask->increment('rest');


        \DB::commit();
        return $userAdTask;

    }


    /**
     * @param UserAdTask|Builder $userAdTask
     */
    public function userAdTaskLog(UserAdTask $userAdTask)
    {
        $ip = $this->getIP();
        $imei = $this->getIMEI();

        //不是进行中状态
        if ($userAdTask->status !== UserAdTaskType::InProgress) return;
        //已过期
        if (Carbon::make($userAdTask->expired_time)->lt(now())) return;

        UserAdTaskLog::query()->create([
            'user_ad_task_id' => $userAdTask->id,
            'ip' => $ip,
            'imei' => $imei,
        ]);
        $userAdTask->increment('now_click_number');
        $user = $userAdTask->user;
        if ($userAdTask->now_click_number >= $userAdTask->complete_click_number && $userAdTask->status == UserAdTaskType::InProgress) {


            $money = $userAdTask->money;

            WalletService::make()->deposit($user, $money, WalletType::balance, WalletLogSlug::interest, WalletLogType::DepositAdTaskInterestByBalance, UserAdTask::class, $userAdTask->id, function (Wallet $wallet, WalletLog $walletLog) use (&$userAdTask) {
                $userAdTask->status = UserAdTaskType::Finished;
                $userAdTask->finished_time = now();
                $userAdTask->log_id = $walletLog->id;
                $userAdTask->adTask()->increment('complete_num');
                $userAdTask->save();
            });

            \Log::info(11111111111);
            $user->notify(new UserAdTaskFinishedNotification($money, $userAdTask));

            \Log::info(22222222222);
            UserHookService::make()->adTaskFinishedHook($userAdTask);
            \Log::info(33333333333);
        }

    }

    public function commissionHandle(UserAdTask $userAdTask)
    {

        $user = $userAdTask->user;
        if (!$user) return;
        $adTask = $userAdTask->adTask;
        if (!$adTask) return;
        //不发放佣金
        if (!$adTask->has_commission) return;
        //获取用户上级
        $user_invite = $user->invite;
        $money = $userAdTask->money;

        $commission_config = $adTask->commission_config;

        $walletService = new WalletService();
        $userInviteAward = UserInviteAward::query()->firstOrCreate(['user_id' => $user->id], [
            'channel_id' => $user->channel_id,
            'link_id' => $user->link_id,
        ]);


        for ($i = 1; $i <= 10; $i++) {
            //上级ID
            $invite_id = data_get($user_invite, 'invite_id_' . $i, 0);
            //没有当前上级
            if ($invite_id <= 0) continue;
            //需要加款的用户
            $invite_user = User::query()->find($invite_id);
            //用户状态禁用
            if (!$invite_user->status) continue;

            $invite_user_vip = $invite_user->vip;

            //获取当前分佣比例
            $p_rate = (float)data_get($commission_config, "parent_" . $i . "_rate", 0);

            //分佣比例
            if ($p_rate <= 0) continue;

            //原则佣金
            $all_fee = round($money * ($p_rate / 100), 8);

            //用户未开通VIP
            if (!$invite_user_vip) {
                $invite_user->notify(new UserAdTaskCommissionNotification(0, $all_fee, true, false, $i, $adTask->vip_level, 0, $user, $userAdTask));
                continue;
            }

            //用户VIP等级小于广告VIP等级，无法获得佣金
            if ($invite_user_vip->level < $adTask->vip_level) {
                $invite_user->notify(new UserAdTaskCommissionNotification(0, $all_fee, true, false, $i, $adTask->vip_level, $invite_user_vip->level, $user, $userAdTask));
                continue;
            }

            //是否得到全部佣金
            $is_get_all_commission = true;

            $invite_vip_money = $money;

            $level_decrease = (float)$adTask->level_decrease;

            //用户VIP等级高于广告任务等级，获得全部佣金
            if ($invite_user_vip->level >= $adTask->vip_level || $level_decrease <= 0) {
                //实际佣金
                $p_fee = round($invite_vip_money * ($p_rate / 100), 8);
            } else {
                $is_get_all_commission = false;
                //实际佣金
                $level_x = $adTask->vip_level - $invite_user_vip->level;
                $p_fee = round(($level_decrease / 100 / $level_x) * $invite_vip_money * ($p_rate / 100), 8);
            }

            if ($p_fee <= 0) continue;

            $walletService->deposit($invite_user, $p_fee, WalletType::balance,
                WalletLogSlug::commission,
                WalletLogType::DepositFriendAdTaskCommission,
                UserAdTask::class, $userAdTask->id, function (Wallet $wallet, WalletLog $walletLog) {

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
            $invite_user->notify(new UserAdTaskCommissionNotification($p_fee, $all_fee, false, $is_get_all_commission, $i, $adTask->vip_level, $invite_user_vip->level, $user, $userAdTask));

        }

    }


    /**
     * 检测广告VIP配置
     * @param User $user
     * @param AdTask $adTask
     */
    public function checkAdTaskVipConfigByUser(User $user, AdTask $adTask)
    {

        abort_if($adTask->total > 0 && $adTask->rest >= $adTask->total, 400, Lang('广告任务可接数量不足'));

        //用户VIP等级
        $user_vip_level = (int)$user->vip?->level;

        //判断用户VIP等级
        abort_if($user_vip_level < $adTask->vip_level, 400, 10001);

        $ad_task_vip_config = collect($adTask->vip_level_max_config)->map(function ($item) {
            return collect($item)->map(fn($i) => intval($i))->toArray();
        })->where('level', "<=", $user_vip_level)->sortByDesc('level')->first();

        $max = (int)data_get($ad_task_vip_config, 'max', 0);
        $day_max = (int)data_get($ad_task_vip_config, 'day_max', 0);


        $user_day_count = $this->getUserAdTaskFinishedCount($user, true, $adTask);

        abort_if($day_max > 0 && $user_day_count >= $day_max, 400, Lang('当前广告今日完成数量已达上限'));

        $user_all_count = $this->getUserAdTaskFinishedCount($user, false, $adTask);
        abort_if($max > 0 && $user_all_count >= $max, 400, Lang('当前广告总完成数量已达上限'));

    }


    /**
     * 检测广告任务是否进行中
     * @param User $user
     * @param AdTask $adTask
     * @return bool
     */
    public function checkUserAdTaskIsInProgress(User $user, AdTask $adTask)
    {
        return UserAdTask::query()->where('ad_task_id', $adTask->id)->where('user_id', $user->id)->inProgress()->exists();

    }


    /**
     * 获取用户今日已消耗次数
     * @param User $user
     * @return int|array
     */
    public function getUserTodayAdTaskCount(User $user, int $get_level = -1)
    {

        $userVip = VipService::make()->getUserVipList($user);

        $ad_task_data = $user->adTasks()
            ->groupBy('level')
            ->select(['level', \DB::raw('count(*) as count')])
            ->today()
            ->orderBy('level')
            ->where(function ($q) {
                $q->expiredCount()
                    ->orWhere(fn($qq) => $qq->inProgress())
                    ->orWhere(fn($qq) => $qq->finished());
            })->get()->toArray();


        $free_task_num = (int)Setting('free_task_num', 1);


        $l_list = collect($userVip)->map(function ($item) {
            return [
                'level' => $item->level,
                'task_num_count' => (int)$item->task_num_count,
            ];
        })->push(['level' => 0, 'task_num_count' => $free_task_num])->sortBy('level')->values()->all();

        $ld = [];

        foreach ($l_list as $item) {
            //当前等级
            $level = $item['level'];
            //当前等级最大任务数
            $level_num = $item['task_num_count'];
            //当前任务已使用次数
            $count = 0;
            foreach ($ad_task_data as $key => $ii) {
                if ($ii['level'] <= $level) {
                    $count += $ii['count'];
                    if ($count >= $level_num) {
                        $ad_task_data[$key]['count'] = $count - $level_num;
                        $count = $level_num;
                    } else {
                        $ad_task_data[$key]['count'] = 0;
                    }

                } else {
                    break;
                }
            }

            $ld['l_' . $level] = $count;
        }

        if ($get_level >= 0) {
            return data_get($ld, "l_" . $get_level, 0);
        }
        return $ld;


    }


    /**
     * 获取用户今日进行中任务数量
     * @param User $user
     * @return int
     */
    public function getUserTodayAdTaskInProgressCount(User $user): int
    {

        return UserAdTask::query()->where('user_id', $user->id)->inProgress()->today()->count();
    }


    /**
     * 获取用户广告任务完成状态的数量
     * @param User $user
     * @param bool $today
     * @param AdTask|null $adTask
     * @return int
     */

    public function getUserAdTaskFinishedCount(User $user, bool $today = false, ?AdTask $adTask = null)
    {

        $orm = UserAdTask::query()->where('user_id', $user->id)->finished();

        if ($adTask) {
            $orm->where('ad_task_id', $adTask->id);
        }

        if ($today) {
            $orm->today();
        }

        return $orm->count();

    }


    /**
     * 获取用户广告数据
     * @param User $user
     * @return int[]
     */
    public function getUserAdTaskData(User $user)
    {
        return [
            'today_finished_count' => $this->getUserAdTaskFinishedCount($user, true),//今日完成次数
        ];
    }


    /**
     * 定时处理用户广告任务数据
     */
    public function disposeUserAdTask()
    {
        \Log::debug("处理用户广告任务数据：" . now());
        UserAdTask::query()->where('expired_time', '<', now())
            ->where('status', UserAdTaskType::InProgress)
            ->chunkById(30, function ($list) {
                foreach ($list as $item) {
                    //\Log::debug("更新未完成的任务为过期：{" . $item->id . "}");
                    /**@var UserAdTask $item */
                    $item->status = UserAdTaskType::HasExpired;
                    $item->save();
                    //过期的任务访问记录删除
                    $item->logs()->delete();
                }
            });

        //过期30天的任务删除
        UserAdTask::query()->where('expired_time', '<', now()->addDays(-30))
            ->where('status', UserAdTaskType::HasExpired)
            ->chunkById(30, function ($list) {
                foreach ($list as $item) {
                    //\Log::debug("删除过期数据：{" . $item->id . "}");
                    /**@var UserAdTask $item */
                    //过期的任务访问记录删除
                    $item->logs()->delete();
                    $item->delete();
                }
            });

    }
}
