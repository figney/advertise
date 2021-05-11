<?php


namespace App\Http\Controllers\Api\V1;


use App\Enums\WalletLogSlug;
use App\Enums\WalletType;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\FriendResource;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\UserAwardRecordResource;
use App\Http\Resources\UserResource;
use App\Models\Notification;
use App\Models\Notifications\UserYesterdayProfitNotification;
use App\Models\UserAwardRecord;
use App\Models\UserSignInLog;
use App\Services\UserHookService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends ApiController
{
    /**
     * UserController constructor.
     */
    public function __construct(protected UserService $userService)
    {
    }


    /**
     * 用户详情-yhxq
     * @authenticated
     * @return \Illuminate\Http\JsonResponse
     * @group 用户-User
     */
    public function info(): \Illuminate\Http\JsonResponse
    {
        try {

            $user = UserService::make()->getUserInfo();
            return $this->response([
                'user' => UserResource::make($user),
            ]);
        } catch (\Exception $exception) {
            return $this->responseError($exception->getMessage());
        }
    }


    /**
     * 绑定邀请人-bdyqr
     * @queryParam invite_id int  required 邀请人ID
     * @authenticated
     * @param Request $request
     * @group 用户-User
     */
    public function setInvite(Request $request)
    {
        try {
            $this->validatorData($request->all(), [
                'invite_id' => 'required',
            ]);
            $user = $this->user();
            $invite_id = $request->input('invite_id');

            UserService::make()->updateInvite($user, $invite_id);

            return $this->responseMessage(Lang("SUCCESS"));

        } catch (\Exception $exception) {
            return $this->responseError($exception->getMessage());
        }

    }

    /**
     * 修改密码-xgmm
     * @queryParam old_password  required 旧密码
     * @queryParam password  required 密码
     * @queryParam password_confirmation  required 确认密码
     * @authenticated
     * @param Request $request
     * @group 用户-User
     */
    public function changePassword(Request $request)
    {
        try {
            $this->validatorData($request->all(), [
                'old_password' => 'required',
                'password' => 'required|confirmed|alpha_dash',
            ]);
            $old_password = $request->input('old_password');
            $password = $request->input('password');
            $user = $this->user();

            abort_if(!\Hash::check($old_password, $user->password), 400, Lang("原密码错误"));
            abort_if($old_password == $password, 400, Lang("新密码不能与旧密码相同"));

            $user->password = \Hash::make($password);
            $user->save();

            return $this->responseMessage(Lang("SUCCESS"));

        } catch (\Exception $exception) {
            return $this->responseError($exception->getMessage());
        }

    }

    /**
     * 修改昵称-xgnc
     * @queryParam name  required 昵称
     * @authenticated
     * @param Request $request
     * @group 用户-User
     */
    public function changeName(Request $request)
    {
        try {
            $this->validatorData($request->all(), [
                'name' => 'required',
            ]);

            $name = $request->input('name');
            $user = $this->user();

            $user->name = $name;
            $user->save();


            return $this->responseMessage(Lang("SUCCESS"));

        } catch (\Exception $exception) {
            return $this->responseError($exception->getMessage());
        }
    }

    /**
     * 我的团队信息-wdttxx
     * @authenticated
     * @group 用户-User
     */
    public function friend()
    {
        $user = $this->user();
        $select = collect();
        $select->add('total_all');
        for ($i = 1; $i <= 10; $i++) {
            $select->add('total_' . $i);
        }
        $invite = $user->invite()->select($select->toArray())->first();

        $total_all = data_get($invite, 'total_all');

        $friend_1 = $user->friend1()->orderByDesc('id')->take(3)->get()->map(function ($item) {
            $item->level = 1;
            return $item;
        })->all();

        $list = collect();

        for ($i = 1; $i <= 10; $i++) {
            $item['level'] = $i;
            $item['total'] = data_get($invite, 'total_' . $i);
            $item['friends'] = [];

            if ($i == 1) {
                $item['friends'] = FriendResource::collection($friend_1);
            }

            $list->add($item);
        }


        $res = [
            'give_balance_earnings' => (float)$user->inviteAward()->value('give_balance'),
            'total_all' => $total_all,
            'list' => $list,

        ];
        return $this->response($res);

    }

    /**
     * 我的团队列表-wdtdlb
     * @queryParam page init  required 页码
     * @queryParam page_size init
     * @queryParam level init  required 层级  1-10
     * @authenticated
     * @param Request $request
     * @group 用户-User
     */
    public function friendList(Request $request)
    {
        try {
            $this->validatorData($request->all(), [
                'page' => 'integer',
                'level' => ['required', Rule::in([1, 2, 3, 4, 5, 6, 7, 8, 9, 10])]
            ]);
            $user = $this->user();

            $page_size = (int)$request->input('page_size', 20);

            $level = (int)$request->input('level');

            $list = $user->friend($level)->paginate($page_size);

            $list = collect($list->items())->map(function ($item) use ($level) {
                $item->level = $level;
                return $item;
            })->all();

            $list = FriendResource::collection($list);

            return $this->response($list);

        } catch (\Exception $exception) {
            return $this->responseError($exception->getMessage());
        }
    }

    /**
     * 好友充值奖励-hyczjl
     * @queryParam page init  required 页码
     * @queryParam page_size init
     * @authenticated
     * @param Request $request
     * @group 用户-User
     */
    public function friendAward(Request $request)
    {
        $user = $this->user();

        $page_size = (int)$request->input('page_size', 20);

        $orm = UserAwardRecord::query()->where('user_id', $user->id)->where('level', '>', 0)->orderByDesc('created_at')->with(['son']);

        $list = $orm->paginate($page_size);

        $list = collect($list->items())->map(function ($item) {
            $item->son->level = $item->level;
            return $item;
        })->all();

        $res['list'] = UserAwardRecordResource::collection($list);
        return $this->response($res);

    }

    /**
     * 获取用户未读消息数-wdxss
     * @authenticated
     * @group 用户-User
     */
    public function unreadNotificationsCount()
    {
        $user = $this->user();
        $count = UserService::make()->getUserUnreadNotifications($user);

        return $this->response(['count' => $count]);

    }

    /**
     * 获取消息列表-hqxxlb
     * @queryParam page init  required 页码
     * @queryParam page_size init  required 每页大小
     * @queryParam forced bool   是否只加载强制提醒
     * @queryParam read string   阅读类型  all read un_read
     * @authenticated
     * @group 用户-User
     */
    public function notifications(Request $request)
    {
        $user = $this->user();

        $page_size = (int)$request->input('page_size');

        $obj = $user->notifications();

        if ($request->boolean('forced')) $obj->where('forced', true);

        $read = $request->input('read', 'all');

        if ($read == 'read') {
            $obj->read();
        }
        if ($read == 'un_read') {
            $obj->unread();
        }

        $list = $obj->paginate($page_size);

        return $this->response(NotificationResource::collection($list));
    }

    /**
     * 标记阅读/删除消息-xxyyd
     *
     * @queryParam ids array  required id数组
     * @queryParam all bool  required 是否全部
     * @queryParam isDelete bool  required 是否删除
     * @group 用户-User
     * @authenticated
     */
    public function notificationRead(Request $request)
    {

        try {
            $this->validatorData($request->all(), [
                'ids' => 'array',
            ]);

            $user = $this->user();
            $ids = $request->input('ids');
            $all = $request->boolean('all');
            $isDelete = $request->boolean('isDelete');
            if ($isDelete) {
                if ($all) {
                    $user->notifications()->delete();
                } else {
                    $user->notifications()->whereIn('_id', $ids)->delete();
                }
            } else {
                if ($all) {
                    $user->unreadNotifications()->update(['read_time' => now(), 'is_read' => true]);
                } else {
                    $user->unreadNotifications()->whereIn('_id', $ids)->update(['read_time' => now(), 'is_read' => true]);
                }
            }


            $count = UserService::make()->getUserUnreadNotifications($user);

            return $this->response(['count' => $count]);

        } catch (\Exception $exception) {
            return $this->responseError($exception->getMessage());
        }

    }

    /**
     * 用户签到信息-qdxx
     * @group 用户-User
     * @authenticated
     */
    public function signInInfo()
    {
        $user = $this->user();

        $signIn = $user->signIn;


        $today_sign_in = $user->todaySignIn();
        if ($user->yesterdaySignIn()) {
            $continuous = $signIn?->continuous ?? 0;
        } else {
            $continuous = $today_sign_in ? 1 : 0;
        }

        $res = [
            'continuous' => $continuous,
            'last_time' => $signIn?->last_time ?? null,
            'today_sign_in' => $today_sign_in,
        ];
        return $this->response($res);

    }

    /**
     * 用户签到-yhqd
     * @group 用户-User
     * @authenticated
     */
    public function signIn(Request $request)
    {

        $user = $this->user();
        $lock = \Cache::lock('UserSignIn:' . $user->id, 10);
        try {
            $lock->block(10);

            $this->validatorData($request->all(), [
                'g_token' => 'required',
            ]);
            $data = $request->all();
            $g_token = $data['g_token'];
            $this->userService->checkGoogleRecaptcha($g_token, $user->national_number);

            UserService::make()->userSignIn($user);
            //用户签到钩子
            UserHookService::make()->signHook($user);

            return $this->responseMessage(Lang('SUCCESS'));

        } catch (\Exception $exception) {
            return $this->responseException($exception);
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * 用户昨日收益通知-zrsytz
     * @group 用户-User
     * @authenticated
     */
    public function getYesterdayProfit()
    {

        $user = $this->user();


        $lock = \Cache::lock('getYesterdayProfit:' . $user->id, 10);
        try {
            $lock->block(10);

            if (Carbon::make($user->created_at)->gt(Carbon::today())) {
                return $this->response([]);
            }

            $is_send = Notification::query()->where('user_id', $user->id)
                ->where('type', 'UserYesterdayProfitNotification')
                ->where('created_at', '>=', Carbon::today())
                ->exists();

            if (!$is_send) {

                $profit = $user->walletLogs()
                    ->where('wallet_type', WalletType::balance)
                    ->where('wallet_slug', WalletLogSlug::interest)
                    ->whereBetween('created_at', [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()])
                    ->sum('fee');

                $commission = $user->walletLogs()
                    ->where('wallet_type', WalletType::balance)
                    ->where('wallet_slug', WalletLogSlug::commission)
                    ->whereBetween('created_at', [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()])
                    ->sum('fee');


                $user->notify(new UserYesterdayProfitNotification($profit, $commission));

                return $this->response([
                    'profit' => $profit,
                    'commission' => $commission,
                ]);
            }

            return $this->response([]);

        } catch (\Exception $exception) {
            return $this->responseException($exception);
        } finally {
            optional($lock)->release();
        }

    }


}
