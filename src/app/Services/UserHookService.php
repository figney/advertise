<?php


namespace App\Services;

use App\Enums\QueueType;
use App\Jobs\InviteHookHandle;
use App\Jobs\RechargeHookHandle;
use App\Jobs\RegisterHookHandle;
use App\Jobs\SignHookHandle;
use App\Models\Notifications\RechargeOrderSuccessNotification;
use App\Models\Notifications\UserEarningsNotification;
use App\Models\Notifications\UserWithdrawToPaySuccessNotification;
use App\Models\User;
use App\Models\UserAdTask;
use App\Models\UserProduct;
use App\Models\UserRechargeOrder;
use App\Models\UserVip;
use App\Models\UserWithdrawOrder;
use App\Models\WalletLog;

/**
 * 用户事件钩子
 * Class UserHookService
 * @package App\Services
 */
class UserHookService extends BaseService
{
    protected TaskService $taskService;
    protected WithdrawService $withdrawService;
    protected WalletService $walletService;

    public function __construct()
    {
        $this->taskService = new TaskService();
        $this->withdrawService = new WithdrawService();
        $this->walletService = new WalletService();
    }


    /**
     * 注册钩子
     * @param User $user
     */
    public function registerHook(User $user)
    {

        //$this->taskService->registerHookHandle($user);

        //队列处理
        dispatch(new RegisterHookHandle($user))->onQueue(QueueType::user);

    }

    /**
     * 登录钩子
     * @param User $user
     */
    public function loginHook(User $user)
    {

    }

    /**
     * 邀请钩子
     * @param int $user_id
     * @param int $invite_user_id
     * @param int $level
     */
    public function inviteHook(int $user_id, int $invite_user_id, int $level)
    {

        //$this->taskService->inviteHookHandle($user_id, $invite_user_id, $level);
        //队列处理
        dispatch(new InviteHookHandle($user_id, $invite_user_id, $level))->onQueue(QueueType::user);
    }

    /**
     * 签到钩子
     * @param User $user
     */
    public function signHook(User $user)
    {
        //直接处理
        //$this->taskService->signHookHandle($user);
        //队列处理
        dispatch(new SignHookHandle($user))->onQueue(QueueType::user);
    }

    /**
     * 充值成功钩子
     * @param UserRechargeOrder $userRechargeOrder
     */
    public function rechargeHook(User $user, UserRechargeOrder $userRechargeOrder)
    {


        //直接处理
        //$this->taskService->rechargeHookHandle($user, $userRechargeOrder);
        //队列处理
        dispatch(new RechargeHookHandle($user, $userRechargeOrder))->onQueue(QueueType::user);

        //发送充值成功通知
        $user->notify(new RechargeOrderSuccessNotification($userRechargeOrder));

    }

    /**
     * 提现钩子
     * @param UserWithdrawOrder $userWithdrawOrder
     */
    public function withdrawHook(UserWithdrawOrder $userWithdrawOrder)
    {
        $user = $userWithdrawOrder->user;

        $user->notify(new UserWithdrawToPaySuccessNotification($userWithdrawOrder, $user));

        //直接执行扣除
        $this->withdrawService->deductAwardHandle($userWithdrawOrder);


    }

    /**
     * 收益发放钩子
     * @param User $user
     */
    public function earningsHook(User $user, WalletLog $walletLog)
    {
        //直接处理
        $this->taskService->earningsHookHandle($user, $walletLog);
        //发送通知
        $user->notify(new UserEarningsNotification($walletLog, $user));

    }

    /**
     * 购买产品钩子
     * @param User $user
     * @param UserProduct $userProduct
     */
    public function buyProductHook(User $user, UserProduct $userProduct)
    {

        //产品购买分佣
        ProductService::make()->commissionHandle($user, $userProduct);

    }

    /**
     * 购买VIP钩子
     * @param User $user
     * @param UserVip $userVip
     */
    public function buyVipHook(User $user, UserVip $userVip, int $number, float $buy_money)
    {

        VipService::make()->commissionHandle($user, $userVip, $number, $buy_money);

    }

    /**
     * 广告任务完成钩子
     * @param UserAdTask $userAdTask
     */
    public function adTaskFinishedHook(UserAdTask $userAdTask)
    {
        AdTaskService::make()->commissionHandle($userAdTask);
    }

}
