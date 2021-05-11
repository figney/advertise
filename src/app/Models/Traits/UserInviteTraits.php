<?php

namespace App\Models\Traits;

use App\Models\User;
use App\Models\UserInvite;

trait UserInviteTraits
{

    public function getInvite($level)
    {
        return $this->hasMany(UserInvite::class, 'invite_id_' . $level);
    }

    public function friend($level)
    {
        return $this->hasManyThrough(User::class, UserInvite::class, 'invite_id_' . $level,'id','id','user_id')->orderByDesc('id')->with(['inviteAward']);
    }

    /**
     * 一级下线关联
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function friend1()
    {
        return $this->hasManyThrough(User::class, UserInvite::class, 'invite_id_1', 'id', 'id','user_id')->with(['inviteAward']);
    }

    public function friend2()
    {
        return $this->hasManyThrough(User::class, UserInvite::class, 'invite_id_2', 'id', 'id','user_id')->with(['inviteAward']);
    }

    public function friend3()
    {
        return $this->hasManyThrough(User::class, UserInvite::class, 'invite_id_3', 'id', 'id','user_id')->with(['inviteAward']);
    }

    public function friend4()
    {
        return $this->hasManyThrough(User::class, UserInvite::class, 'invite_id_4', 'id', 'id','user_id')->with(['inviteAward']);
    }

    public function friend5()
    {
        return $this->hasManyThrough(User::class, UserInvite::class, 'invite_id_5', 'id', 'id','user_id')->with(['inviteAward']);
    }

    public function friend6()
    {
        return $this->hasManyThrough(User::class, UserInvite::class, 'invite_id_6', 'id', 'id','user_id')->with(['inviteAward']);
    }

    public function friend7()
    {
        return $this->hasManyThrough(User::class, UserInvite::class, 'invite_id_7', 'id', 'id','user_id')->with(['inviteAward']);
    }

    public function friend8()
    {
        return $this->hasManyThrough(User::class, UserInvite::class, 'invite_id_8', 'id', 'id','user_id')->with(['inviteAward']);
    }

    public function friend9()
    {
        return $this->hasManyThrough(User::class, UserInvite::class, 'invite_id_9', 'id', 'id','user_id')->with(['inviteAward']);
    }

    public function friend10()
    {
        return $this->hasManyThrough(User::class, UserInvite::class, 'invite_id_10', 'id', 'id','user_id')->with(['inviteAward']);
    }
}
