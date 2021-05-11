<?php


namespace App\Http\Controllers\Api\V1;


use App\Enums\TaskTargetType;
use App\Enums\UserHookType;
use App\Enums\WalletType;
use App\Enums\WithdrawOrderStatusType;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends ApiController
{

    public function __construct(protected TaskService $taskService)
    {
    }

    /**
     * 获取单个任务-wqdgrw
     * @queryParam hook  required 触发事件
     * @queryParam task_target  required 触发事件
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTaskInfo(Request $request)
    {

        try {
            $this->validatorData($request->all(), [
                'hook' => ['required', Rule::in(UserHookType::getValues())],
                'task_target' => ['required', Rule::in(TaskTargetType::getValues())],
            ]);

            $task = $this->taskService->getTaskOrm()->where('task_target', $request->input('task_target'))->where('hook', $request->input('hook'))->first();

            return $this->response($task ? TaskResource::make($task) : null);
        } catch (\Exception $exception) {

        }


    }

    /**
     * 任务列表-rwlb
     * @queryParam hook  required  事件类型
     * @param Request $request
     */
    public function taskList(Request $request)
    {
        $orm = $this->taskService->getTaskOrm()->where('is_show', true)->orderByDesc('order')->orderBy('id');

        $hook = $request->input('hook');

        if ($hook) $orm->where('hook', $hook);

        $user = $this->user();

        $all_withdraw = 0;
        $day_all_withdraw = 0;
        if ($user) {

            $orm->with(['userTask' => fn($q) => $q->where('user_id', $user->id)]);


            $balance_withdraw = (float)$user->walletCount->balance_withdraw;
            $usdt_balance_withdraw = (float)$user->walletCount->usdt_balance_withdraw;
            //用户总提现金额
            $all_withdraw = $balance_withdraw + ($usdt_balance_withdraw * (float)Setting('usdt_money_rate'));

            $day_balance_withdraw = (float)$user->withdrawOrders()
                ->where('created_at', '>=', Carbon::today())
                ->where('wallet_type', WalletType::balance)
                ->whereIn('order_status', [WithdrawOrderStatusType::CheckSuccess, WithdrawOrderStatusType::Checking, WithdrawOrderStatusType::Paying])
                ->sum('actual_amount');
            $day_usdt_balance_withdraw = (float)$user->withdrawOrders()
                ->where('created_at', '>=', Carbon::today())
                ->where('wallet_type', WalletType::usdt)
                ->whereIn('order_status', [WithdrawOrderStatusType::CheckSuccess, WithdrawOrderStatusType::Checking, WithdrawOrderStatusType::Paying])
                ->sum('actual_amount');
            //用户总提现金额
            $day_all_withdraw = $day_balance_withdraw + ($day_usdt_balance_withdraw * (float)Setting('usdt_money_rate'));
        }


        $list = $orm->get()->filter(function (Task $task) use ($user) {

            if (!$user) return true;

            //未充值用户
            if ($user->recharge_count <= 0 && $task->user_type == 2) {
                return false;
            }

            //已充值用户
            if ($user->recharge_count > 0 && $task->user_type == 1) {
                return false;
            }

            $userTask = $task->userTask;

            //没触发过
            if (!$userTask) return true;
            //允许重复
            if ($task->repetition) return true;
            //未完成
            if (!$userTask->achieve) return true;

            return false;

        })->map(function (Task $task) use ($day_all_withdraw, $all_withdraw, $user) {
            if (!$user) return $task;

            if ($task->hook == UserHookType::Recharge && $task->userTask) {
                //判断提现是否参与
                $t_all_withdraw = 0;
                $t_day_all_withdraw = 0;
                if ($task->check_withdraw) {
                    //用户总提现金额
                    $t_all_withdraw = $all_withdraw;
                    $t_day_all_withdraw = $day_all_withdraw;
                }

                if ($task->task_target == TaskTargetType::Accomplish) $task->userTask->target_condition = $task->userTask->target_condition - $t_all_withdraw;
                if (in_array($task->task_target, [TaskTargetType::ContinuousDay, TaskTargetType::ContinuousDayIncrease])) $task->userTask->target_condition = $task->userTask->target_condition - $t_day_all_withdraw;

            }

            return $task;
        })->all();

        $res['list'] = TaskResource::collection($list);

        return $this->response($res);
    }

}
