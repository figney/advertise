<?php

namespace App\Models;

use App\Enums\MoneyBaoStatusType;
use App\Enums\WalletLogSlug;
use App\Enums\WalletType;
use App\Enums\WithdrawOrderStatusType;
use App\Models\Traits\AdminDataScope;
use App\Models\Traits\UserInviteTraits;
use App\Services\AdTaskService;
use App\Services\ProductService;
use App\Traits\UserNotifiable;

use Carbon\Carbon;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Jenssegers\Mongodb\Eloquent\HybridRelations;
use Tymon\JWTAuth\Contracts\JWTSubject;


class User extends Authenticatable implements JWTSubject
{
    use UserInviteTraits, UserNotifiable, HasDateTimeFormatter, HybridRelations, AdminDataScope;

    protected $connection = 'mysql';

    protected $guarded = [];

    protected $casts = [
        'activity' => 'boolean',
        'status' => 'boolean',
    ];

    protected $dateFormat = "Y-m-d H:i:s";

    /**
     * 充值订单关联
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\Jenssegers\Mongodb\Relations\HasMany
     */
    public function rechargeOrders()
    {
        return $this->hasMany(UserRechargeOrder::class);
    }

    public function withdrawOrders()
    {
        return $this->hasMany(UserWithdrawOrder::class);
    }

    public function withdrawOrdersChecking()
    {
        return $this->hasMany(UserWithdrawOrder::class)->where('order_status', WithdrawOrderStatusType::Checking);
    }

    public function inviteAward()
    {
        return $this->hasOne(UserInviteAward::class);
    }

    //邀请关系关联
    public function invite()
    {
        return $this->hasOne(UserInvite::class);
    }

    //渠道关联
    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    //上级关联
    public function parent()
    {
        return $this->belongsTo(User::class, 'invite_id');
    }

    //绑定客服关联
    public function channelService()
    {
        return $this->belongsTo(ChannelService::class)->where('status', 1);
    }

    //钱包关联
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    //钱包统计数据关联
    public function walletCount()
    {
        return $this->hasOne(WalletCount::class);
    }

    //钱包流水数据关联
    public function walletLogs()
    {
        return $this->hasMany(WalletLog::class);
    }

    //钱包流水数据关联
    public function WalletLogMongo()
    {
        return $this->hasMany(WalletLogMongo::class);
    }

    //赚钱宝关联
    public function moneyBao()
    {
        return $this->hasOne(MoneyBao::class);
    }

    //投资产品关联
    public function products()
    {
        return $this->hasMany(UserProduct::class);
    }

    //用户设备列表关联
    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    //用户设备IP
    public function ips()
    {
        return $this->hasMany(Device::class, 'ip', 'ip')->where('user_id', '>', 0);
    }

    //用户imei设备关联
    public function device()
    {
        return $this->hasOne(Device::class, 'imei', 'imei');
    }

    /**
     * 用户转账凭证关联
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\Jenssegers\Mongodb\Relations\HasMany|UserTransferVoucher[]
     */
    public function transferVoucher()
    {
        return $this->hasMany(UserTransferVoucher::class);
    }

    /**
     * 用户签到关联
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|\Jenssegers\Mongodb\Relations\HasOne
     */
    public function signIn()
    {
        return $this->hasOne(UserSignIn::class);
    }

    /**
     * 用户签到记录关联
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\Jenssegers\Mongodb\Relations\HasMany
     */
    public function signInLogs()
    {
        return $this->hasMany(UserSignInLog::class);
    }


    /**
     * 用户VIP关联
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|\Jenssegers\Mongodb\Relations\HasOne|UserVip|null
     */
    public function vip()
    {
        return $this->hasOne(UserVip::class)
            ->orderByDesc('level')->where('expire_time', '>', now());

    }

    public function vips()
    {
        return $this->hasMany(UserVip::class)->orderByDesc('level')->where('expire_time', '>', now());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\Jenssegers\Mongodb\Relations\HasMany|UserAdTask
     */
    public function adTasks()
    {
        return $this->hasMany(UserAdTask::class);
    }

    /********************************/

    /**
     * 判断用户是某个等级的VIP，高于查询等级的VIP也算
     * @param $level
     * @return bool
     */
    public function isVipByLevel($level)
    {
        return $this->vips()->where('level', '>=', $level)->exists();
    }


    /**
     * 今日是否签到
     * @return bool
     */
    public function todaySignIn(): bool
    {
        return $this->signInLogs()->where('created_at', '>=', Carbon::today())->exists();
    }

    public function yesterdaySignIn(): bool
    {
        return $this->signInLogs()->whereBetween('created_at', [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()])->exists();
    }

    /**
     * 用户是否充值
     * @return bool
     */
    public function hasRecharge(): bool
    {
        return $this->recharge_count > 0;
    }

    /**
     * 用户定期产品数据
     * @return array
     */
    public function productData()
    {
        return ProductService::make()->getUserProductData($this);
    }


    public function adTaskData()
    {
        return AdTaskService::make()->getUserAdTaskData($this);
    }

    //********************************************

    public function getDayInterestAttribute()
    {
        $list = $this->walletLogs()->where('created_at', '>=', Carbon::yesterday())->whereIn('wallet_slug', [WalletLogSlug::interest, WalletLogSlug::commission])->get(['fee', 'created_at']);

        $res['yesterday'] = $list->filter(function ($item) {
            return Carbon::make($item->created_at)->lt(Carbon::today());
        })->sum('fee');

        $res['today'] = $list->filter(function ($item) {
            return Carbon::make($item->created_at)->gte(Carbon::today());
        })->sum('fee');

        return $res;
    }

    /**
     * 用户hashID访问器
     * @return string
     */
    public function getHashAttribute()
    {
        return \Hashids::encode($this->id);
    }


    /**
     * 用户总资产访问器
     */
    public function getAllPropertyAttribute(): array
    {
        //总资产 = 赚钱宝余额 + 钱包余额 + 定期产品投资额

        //冻结金额 = 赚钱宝余额 + 定期产品投资额

        $data[WalletType::balance] = [
            'property' => (float)($this->moneyBao->balance + $this->wallet->balance + $this->walletCount->product_amount),
            'freeze' => (float)($this->moneyBao->balance + $this->walletCount->product_amount),
            'usable' => (float)$this->wallet->balance,
        ];

        $data[WalletType::usdt] = [
            'property' => (float)($this->moneyBao->usdt_balance + $this->wallet->usdt_balance + $this->walletCount->product_usdt_amount),
            'freeze' => (float)($this->moneyBao->usdt_balance + $this->walletCount->product_usdt_amount),
            'usable' => (float)$this->wallet->usdt_balance,
        ];

        $data[WalletType::give] = [
            'property' => (float)($this->moneyBao->give_balance + $this->wallet->give_balance),
            'freeze' => (float)($this->moneyBao->give_balance),
            'usable' => (float)($this->wallet->give_balance),
        ];

        return $data;
    }


    //********************************************

    /**
     * 绑定客服
     * @param ChannelService $channelService
     */
    public function bindChannelService(ChannelService $channelService)
    {
        $this->channel_service_id = $channelService->id;
        $this->save();

    }


    public static function testerIds()
    {
        return self::query()->where('tester', 1)->pluck('id');
    }

    protected static function booted()
    {
        //创建用户事件
        static::created(function (User $user) {
            //初始化用户钱包
            Wallet::userInit($user);

            //初始化赚钱宝
            $user->moneyBao()->create([
                'user_id' => $user->id,
                'link_id' => $user->link_id,
                'channel_id' => $user->channel_id,
                'last_grant_time' => now(),
                'last_time' => now(),
                'usdt_last_time' => now(),
                'give_last_time' => now(),
                'balance_status' => MoneyBaoStatusType::stop,
                'usdt_status' => MoneyBaoStatusType::stop,
                'give_status' => MoneyBaoStatusType::stop,
            ]);

        });
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }


}
