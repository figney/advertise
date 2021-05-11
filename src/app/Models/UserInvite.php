<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class UserInvite extends Model
{
    use HasFactory, HasDateTimeFormatter;

    protected $guarded = [];

    protected $table = "user_invite";


    protected $appends = [];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTotalAttribute()
    {
        $total = 0;
        for ($i = 1; $i <= 10; $i++) {
            $total = $total + data_get($this, 'total_' . $i, 0);
        }
        return $total;
    }


    /**
     * 更新用户下线数量统计
     * @param $user_id
     */
    public static function updateTotal($user_id)
    {
        $counts = [];
        $total_all = 0;
        for ($i = 1; $i <= 10; $i++) {
            $count = self::query()->where("invite_id_" . $i, $user_id)->count();
            if ($count <= 0) {
                break;
            }
            $counts['total_' . $i] = $count;
            $total_all += $count;
        }

        $counts['total_all'] = $total_all;

        self::query()->where('user_id', $user_id)->update($counts);
    }


    protected static function booted()
    {
        //创建事件
        static::created(function (UserInvite $userInvite) {
            //更新上级的下线统计
            for ($i = 1; $i <= 10; $i++) {
                $invite_id = data_get($userInvite, "invite_id_" . $i);

                if ($invite_id > 0) {//有上级
                    //更新上级用户的下线数量
                    self::query()->where('user_id', $invite_id)->increment('total_' . $i);
                    //插入邀请记录
                    UserInviteLog::query()->create([
                        'user_id' => $userInvite->user_id,
                        'invite_id' => $invite_id,
                        'channel_id' => $userInvite->channel_id,
                        'level' => $i,
                    ]);
                }
            }

        });

    }
}
