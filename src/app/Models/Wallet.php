<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class Wallet extends Model
{


    public $timestamps = false;
    protected $table = 'wallet';

    protected $guarded = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function walletCount()
    {
        return $this->hasOne(WalletCount::class);
    }


    /**
     * @param User $user
     */
    public static function userInit(User $user)
    {
        $wallet = self::query()->create([
            'user_id' => $user->id,
            'user_level' => 0,
            'channel_id' => $user->channel_id,
            'link_id' => $user->link_id,
        ]);
        $wallet->walletCount()->create([
            'user_id' => $user->id,
            'user_level' => 0,
            'channel_id' => $user->channel_id,
            'link_id' => $user->link_id,
        ]);
    }

}
