<?php

namespace App\Console\Commands;

use App\Enums\TaskTargetType;
use App\Enums\UserHookType;
use App\Models\Task;
use App\Models\UserTask;
use Illuminate\Console\Command;

class RepairUserTaskData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:RepairUserTaskData';


    protected $description = '修复用户累计充值任务数据';


    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {

        $t_ids = Task::query()
            ->where('hook', UserHookType::Recharge)
            ->where('task_target', TaskTargetType::Accomplish)
            ->pluck('id');

        UserTask::query()->with(['user'])->whereIn('task_id', $t_ids)->where('achieve', 0)
            ->chunkById(50, function ($list) {
                /**@var UserTask $userTask */
                foreach ($list as $userTask) {
                    $user = $userTask->user;
                    $balance_recharge = (float)$user->walletCount->balance_recharge;
                    $usdt_balance_recharge = (float)$user->walletCount->usdt_balance_recharge;
                    //用户总提现金额
                    $all_recharge = $balance_recharge + ($usdt_balance_recharge * (float)Setting('usdt_money_rate'));
                    if ((float)$userTask->target_condition !== (float)$all_recharge) {

                        $userTask->target_condition = $all_recharge;
                        $userTask->save();

                        $this->info("更新用户：" . $user->id . '-' . $userTask->target_condition . '-' . $all_recharge);
                    }

                }
            });

    }
}
