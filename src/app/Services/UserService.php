<?php

namespace App\Services;

use App\Enums\QueueType;
use App\Models\Device;
use App\Models\RecaptchaErrorLog;
use App\Models\User;
use App\Models\UserInvite;
use App\Models\UserInviteLog;
use App\Models\UserSignIn;
use App\Models\UserSignInLog;
use Carbon\Carbon;
use Faker\Factory;
use ReCaptcha\ReCaptcha;

class UserService extends BaseService
{

    public function getUserInfo($user = null)
    {
        $user = $user ?? $this->user();

        $user->load('invite');
        $user->load('wallet');
        $user->load('wallet.walletCount');
        //$user->load('moneyBao');
        $user->load('parent');
        $user->load('inviteAward');
        $user->load('vips');
        $user->load('vips.vip');


        return $user;
    }


    /**
     * 创建用户
     * @param $data
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|User
     */
    public function createUser($data)
    {

        $this->checkDevice();

        $this->checkNationalNumber(data_get($data, 'national_number'));

        //开启事务
        \DB::beginTransaction();
        $channel_id = data_get($data, 'channel_id', 0);
        $link_id = data_get($data, 'link_id', 0);

        $source = data_get($data, 'source');
        $ip = $this->getIP();
        $invite_id = data_get($data, 'invite_id', 0);
        //如果有邀请码
        $invite_user = null;
        if ($invite_id > 0) {
            $invite_user = User::query()->find($invite_id);
            if ($invite_user) {
                $channel_id = $invite_user->channel_id;
                $link_id = $invite_user->link_id;

            } else {
                $invite_id = 0;
            }
        }

        if ($source !== "ad") abort_if($invite_id <= 0, 400, Lang('邀请人不存在'));


        $national_number = data_get($data, 'national_number');

        $name = data_get($data, 'name');


        $password = \Hash::make(data_get($data, 'password'));
        //创建用户数据
        $user = User::query()->create([
            'national_number' => $national_number,
            'password' => $password,
            'country_calling_code' => data_get($data, 'country_calling_code'),
            'name' => $name,
            'channel_id' => $channel_id,
            'link_id' => $link_id,
            'invite_id' => $invite_id,
            'source' => $source,
            'country_code' => data_get($data, 'country_code'),
            'ip' => $ip,
            'imei' => $this->getIMEI(),
            'local' => $this->getLocal(),
            'lang' => $this->getAgentLanguage(),
            'last_active_at' => now(),
            'last_login_time' => now(),
        ]);

        //处理邀请关系
        $userInvite = $this->createUserInvite($user, $invite_user, $channel_id);

        //提交事务
        \DB::commit();

        $user->wallet()->update(['user_level' => $userInvite->level]);
        $user->walletCount()->update(['user_level' => $userInvite->level]);
        $user->device()->where('user_id', 0)->update(['user_id' => $user->id, 'channel_id' => $user->channel_id, 'link_id' => $user->link_id]);

        for ($i = 1; $i <= 10; $i++) {
            $invite_id = data_get($user->invite, "invite_id_" . $i, 0);
            if ($invite_id > 0) {
                //触发邀请钩子
                if ($i == 1) UserHookService::make()->inviteHook($user->id, $invite_id, $i);

                dispatch(function () use ($invite_id) {
                    UserInvite::updateTotal($invite_id);
                })->onQueue(QueueType::user);
            }
        }

        return $user;

    }

    /**
     * 检测手机号码
     * @param $national_number
     */
    public function checkNationalNumber($national_number)
    {
        abort_if(User::query()->where('national_number', $national_number)->count() > 0, 400, Lang("手机号码已存在"));
    }

    public function checkDevice()
    {
        $imei = $this->getIMEI();
        $ip = $this->getIP();

        $ip_count = Device::query()->where('ip', $ip)->where('user_id', '>', 0)->count();

        if (\App::isProduction()) {
            abort_if($ip_count >= 3, 400, 'error 1');
        }


        $count = Device::query()->where('imei', $imei)->where('user_id', '>', 0)->count();

        abort_if($count >= Setting('device_reg_max'), 400, 'error 2');

    }


    /**
     * 创建邀请关系
     * @param User $user 当前用户
     * @param User|null $invite_user 邀请人
     * @param int $channel_id 渠道ID
     */
    private function createUserInvite(User $user, ?User $invite_user, $channel_id = 0)
    {

        $data = [
            'user_id' => $user->id,
            'link_id' => $user->link_id,
            'invite_id_1' => 0,
            'channel_id' => $channel_id,
            'level' => 0,
        ];
        //获取上级好友关系
        if ($invite_user) {
            //获取邀请人
            $parents = UserInvite::query()->where('user_id', $invite_user->id)->first();
            $data['invite_id_1'] = $invite_user->id;
            $data['channel_id'] = $invite_user->channel_id;
            $data['level'] = $parents->level + 1;
            if ($parents) {
                $data['invite_id_2'] = $parents->invite_id_1;
                $data['invite_id_3'] = $parents->invite_id_2;
                $data['invite_id_4'] = $parents->invite_id_3;
                $data['invite_id_5'] = $parents->invite_id_4;
                $data['invite_id_6'] = $parents->invite_id_5;
                $data['invite_id_7'] = $parents->invite_id_6;
                $data['invite_id_8'] = $parents->invite_id_7;
                $data['invite_id_9'] = $parents->invite_id_8;
                $data['invite_id_10'] = $parents->invite_id_9;
            }
        }


        //写入当前用户好友关系
        return UserInvite::query()->create($data);
    }

    /**
     * 更新邀请关系
     * @param User $user
     * @param int $invite_id
     */
    public function updateInvite(User $user, int $invite_id)
    {

        abort_if($user->invite_id > 0, 400, Lang("已绑定邀请人"));

        abort_if($user->id == $invite_id, 400, Lang("不能绑定自己"));

        $invite_user = User::query()->find($invite_id);

        abort_if(!$invite_user, 400, Lang("邀请人不存在"));


        //获取邀请人关系
        $parents = UserInvite::query()->where('user_id', $invite_user->id)->first();

        //判断当前用户是否是邀请人的有效层级的上级
        for ($i = 1; $i <= 10; $i++) {
            $invite_id = data_get($parents, "invite_id_" . $i, 0);
            if ($invite_id === $user->id) {
                abort(400, "无法绑定下级为邀请人");
            }
        }
        \DB::beginTransaction();

        //更新当前用户关系
        $data['level'] = $parents->level + 1;
        $data['invite_id_1'] = $invite_user->id;
        $data['invite_id_2'] = $parents->invite_id_1;
        $data['invite_id_3'] = $parents->invite_id_2;
        $data['invite_id_4'] = $parents->invite_id_3;
        $data['invite_id_5'] = $parents->invite_id_4;
        $data['invite_id_6'] = $parents->invite_id_5;
        $data['invite_id_7'] = $parents->invite_id_6;
        $data['invite_id_8'] = $parents->invite_id_7;
        $data['invite_id_9'] = $parents->invite_id_8;
        $data['invite_id_10'] = $parents->invite_id_9;
        UserInvite::query()->where('user_id', $user->id)->update($data);
        //更新当前用户下级的附加关系
        for ($i = 1; $i <= 9; $i++) {
            $ii = $i + 1;
            $son = UserInvite::query()->where('invite_id_' . $i, $user->id)->where('invite_id_' . $ii, 0)->first();

            if ($son) {
                $son->update([
                    'invite_id_' . $ii => $invite_user->id,
                    'level' => \DB::raw('level +1')
                ]);
                $son_user = $son->user;
                $son_user->wallet()->update(['user_level' => $son->level + 1]);
                $son_user->walletCount()->update(['user_level' => $son->level + 1]);
                $son_user->transferVoucher()->update(['user_level' => $son->level + 1]);
                //插入邀请记录
                UserInviteLog::query()->create([
                    'user_id' => $son->user_id,
                    'channel_id' => $invite_user->channel_id,
                    'link_id' => $invite_user->link_id,
                    'invite_id' => $invite_user->id,
                    'level' => $ii,
                ]);
            } else {
                break;
            }
        }
        //更新上级用户下线数量统计
        for ($i = 1; $i <= 10; $i++) {
            $invite_id = data_get($data, "invite_id_" . $i, 0);
            if ($invite_id > 0) {//有上级
                //更新上级用户的下线数量
                UserInvite::updateTotal($invite_id);
                //插入邀请记录
                UserInviteLog::query()->create([
                    'user_id' => $user->id,
                    'channel_id' => $user->channel_id,
                    'invite_id' => $invite_id,
                    'level' => $i,
                ]);
                //触发邀请钩子
                if ($i == 1) UserHookService::make()->inviteHook($user->id, $invite_id, $i);
            }
        }
        //更新用户表
        $user->update([
            'invite_id' => $invite_user->id
        ]);
        \DB::commit();

    }

    /**
     * 用户激活
     * @param User $user
     */
    public function userActivity(User $user)
    {
        $user->activity = true;
        $user->invite->activity = true;
        $user->push();
        //TODO 用户激活后续逻辑


    }


    /**
     * 获取用户未读消息数量
     * @param User $user
     * @return mixed
     */
    public function getUserUnreadNotifications(User $user)
    {
        return $user->unreadNotifications()->count();
    }


    /**
     * 用户签到
     */
    public function userSignIn(User $user)
    {
        abort_if($user->todaySignIn(), 400, Lang('今日已签到'));

        $userSignIn = UserSignIn::query()->firstOrCreate(['user_id' => $user->id], [
            'continuous' => 1,
            'channel_id' => $user->channel_id,
            'link_id' => $user->link_id,
            'last_time' => now(),
        ]);
        //写入签到记录
        UserSignInLog::query()->create([
            'user_id' => $user->id,
            'channel_id' => $user->channel_id,
            'link_id' => $user->link_id,
            'ip' => $this->getIP(),
            'imei' => $this->getIMEI(),
        ]);


        if ($user->yesterdaySignIn()) {
            $userSignIn->increment('continuous');
            $userSignIn->last_time = now();
            $userSignIn->save();
        } else {
            $userSignIn->continuous = 1;
            $userSignIn->last_time = now();
            $userSignIn->save();
        }

    }


    /**
     * 检测谷歌验证
     * @param $g_token
     * @return array
     */
    public function checkGoogleRecaptcha($g_token, $national_number = null)
    {
        if (SettingBool('open_recaptcha')) {
            $recaptcha = new ReCaptcha(Setting('google_serve_recaptcha'));
            $resp = $recaptcha
                //->setExpectedHostname(collect(Setting('google_check_domains'))->join(','))
                ->verify($g_token, $this->getIP());

            if ($resp->isSuccess()) {
                return $resp->toArray();
            } else {
                RecaptchaErrorLog::query()->create([
                    'imei' => $this->getIMEI(),
                    'ip' => $this->getIP(),
                    'national_number' => $national_number,
                    'recaptcha' => $resp->toArray()
                ]);
                abort(400, collect($resp->getErrorCodes())->first());
            }
        } else {
            return [];
        }

    }

}
