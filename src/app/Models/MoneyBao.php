<?php

namespace App\Models;

use App\Enums\MoneyBaoStatusType;
use App\Enums\WalletLogType;
use Carbon\Carbon;
use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;

class MoneyBao extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'user_money_bao';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'has' => 'bool'
    ];


    /**
     * 关联用户
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getMoneyBaoCountAttribute($key)
    {

        $day = Carbon::yesterday();
        $cache_key = $this->user_id . $day;
        $yesterday_balance_interest = 0;
        if ($this->balance_interest > 0) {
            $yesterday_balance_interest = \Cache::remember("yesterday_balance_interest_" . $cache_key, 60 * 60 * 24, function () use ($day) {
                return WalletLog::query()->where('user_id', $this->user_id)->whereDate('created_at', $day)
                        ->where('action_type', WalletLogType::DepositMoneyBaoInterestByBalance)->value('fee') ?? 0;
            });
        }
        $yesterday_usdt_balance_interest = 0;
        if ($this->usdt_balance_interest > 0) {
            $yesterday_usdt_balance_interest = \Cache::remember("yesterday_usdt_balance_interest_" . $cache_key, 60 * 60 * 24, function () use ($day) {
                return WalletLog::query()->where('user_id', $this->user_id)->whereDate('created_at', $day)
                        ->where('action_type', WalletLogType::DepositMoneyBaoInterestByUSDT)->value('fee') ?? 0;
            });
        }
        $yesterday_give_balance_earnings = 0;
        if ($this->give_balance_earnings > 0) {
            $yesterday_give_balance_earnings = \Cache::remember("yesterday_give_balance_earnings_" . $cache_key, 60 * 60 * 24, function () use ($day) {
                return WalletLog::query()->where('user_id', $this->user_id)->whereDate('created_at', $day)
                        ->where('action_type', WalletLogType::DepositMoneyBaoInterestByGive)->value('fee') ?? 0;
            });
        }
        return [
            'balance_earnings' => (float)$this->balance_earnings,
            'balance_interest' => (float)$this->balance_interest,
            'yesterday_balance_interest' => (float)$yesterday_balance_interest,
            'usdt_balance_earnings' => (float)$this->usdt_balance_earnings,
            'usdt_balance_interest' => (float)$this->usdt_balance_interest,
            'yesterday_usdt_balance_interest' => (float)$yesterday_usdt_balance_interest,
            'give_balance_earnings' => (float)$this->give_balance_earnings,
            'yesterday_give_balance_earnings' => (float)$yesterday_give_balance_earnings,
        ];
    }

    public function dayStatus()
    {
        //计算每种金额每天的利息
        $mb_balance_rate = (float)Setting('mb_balance_rate');//法币年化
        $mb_usdt_rate = (float)Setting('mb_usdt_rate');//USDT年化
        $mb_give_rate = (float)Setting('mb_give_rate');//赠送金年化

        $day_mb_balance_rate = $mb_balance_rate / 365 / 100;
        $balance_fee = $this->balance * $day_mb_balance_rate;


        $day_mb_usdt_rate = $mb_usdt_rate / 365 / 100;
        $usdt_balance_fee = $this->usdt_balance * $day_mb_usdt_rate;


        $day_mb_give_rate = $mb_give_rate / 365 / 100;
        $give_balance_fee = $this->give_balance * $day_mb_give_rate;


        $data['give'] = [
            'status' => MoneyBaoStatusType::stop,
            'residue_s' => 0,
            'fee' => 0,
        ];
        $data['balance'] = [
            'status' => MoneyBaoStatusType::stop,
            'residue_s' => 0,
            'fee' => 0,
        ];
        $data['usdt'] = [
            'status' => MoneyBaoStatusType::stop,
            'residue_s' => 0,
            'fee' => 0,
        ];
        if ($this->balance > 0) {
            //最后存入时间
            $last_time = $this->last_time ? $this->last_time : $this->last_grant_time;
            //已到24小时，需要手动领取收益
            if (Carbon::make($last_time)->addDays()->lte(now())) {
                $data['balance'] = [
                    'status' => MoneyBaoStatusType::over,
                    'fee' => $balance_fee,
                    'residue_s' => 0,
                ];
            } else {
                $data['balance'] = [
                    'status' => MoneyBaoStatusType::working,
                    'fee' => $balance_fee,
                    'residue_s' => Carbon::make($last_time)->addDays()->floatDiffInSeconds(now()),
                ];
            }
        }
        if ($this->usdt_balance > 0) {
            //最后存入时间
            $usdt_last_time = $this->usdt_last_time ? $this->usdt_last_time : $this->last_grant_time;
            //已到24小时，需要手动领取收益
            if (Carbon::make($usdt_last_time)->addDays()->lte(now())) {
                $data['usdt'] = [
                    'status' => MoneyBaoStatusType::over,
                    'fee' => $usdt_balance_fee,
                    'residue_s' => 0,
                ];
            } else {
                $data['usdt'] = [
                    'status' => MoneyBaoStatusType::working,
                    'fee' => $usdt_balance_fee,
                    'residue_s' => Carbon::make($usdt_last_time)->addDays()->floatDiffInSeconds(now()),
                ];
            }
        }
        if ($this->give_balance > 0) {
            //最后存入时间
            $give_last_time = $this->give_last_time ? $this->give_last_time : $this->last_grant_time;
            //已到24小时，需要手动领取收益
            if (Carbon::make($give_last_time)->addDays()->lte(now())) {
                $data['give'] = [
                    'status' => MoneyBaoStatusType::over,
                    'fee' => $give_balance_fee,
                    'residue_s' => 0,
                ];
            } else {
                $data['give'] = [
                    'status' => MoneyBaoStatusType::working,
                    'fee' => $give_balance_fee,
                    'residue_s' => Carbon::make($give_last_time)->addDays()->floatDiffInSeconds(now()),
                ];
            }
        }
        return $data;

    }

    public function dayData()
    {

        //剩余秒数
        $residue_s = Carbon::tomorrow()->floatDiffInSeconds(now());

        //已走秒数
        $go_s = now()->floatDiffInSeconds(Carbon::today());

        //计算每种金额每天的利息
        $mb_balance_rate = (float)Setting('mb_balance_rate');//法币年化
        $mb_usdt_rate = (float)Setting('mb_usdt_rate');//USDT年化
        $mb_give_rate = (float)Setting('mb_give_rate');//赠送金年化

        $day_mb_balance_rate = $mb_balance_rate / 365 / 100;
        $balance_fee = $this->balance * $day_mb_balance_rate;


        //usdt利息存入USDT余额
        $day_mb_usdt_rate = $mb_usdt_rate / 365 / 100;
        $usdt_balance_fee = $this->usdt_balance * $day_mb_usdt_rate;

        $day_mb_give_rate = $mb_give_rate / 365 / 100;
        $give_balance_fee = $this->give_balance * $day_mb_give_rate;


        return [
            'balance' => [
                'day_fee' => $balance_fee,
                'this_fee' => $balance_fee / 86400 * $go_s,
                'residue_s' => $residue_s,
            ],
            'usdt' => [
                'day_fee' => $usdt_balance_fee,
                'this_fee' => $usdt_balance_fee / 86400 * $go_s,
                'residue_s' => $residue_s,
            ],
            'give' => [
                'day_fee' => $give_balance_fee,
                'this_fee' => $give_balance_fee / 86400 * $go_s,
                'residue_s' => $residue_s,
            ]
        ];

    }


    protected static function booted()
    {
        static::updated(function (MoneyBao $moneyBao) {

            $c = (float)$moneyBao->balance + (float)$moneyBao->usdt_balance + (float)$moneyBao->give_balance;

            if ($c > 0 && !$moneyBao->has) {
                $moneyBao->update(['has' => true]);
            }
            if ($c <= 0 && $moneyBao->has) {
                $moneyBao->update(['has' => false]);
            }

        });
        static::deleting(function (MoneyBao $moneyBao) {
            abort(400, "无法删除");
        });
    }

}
