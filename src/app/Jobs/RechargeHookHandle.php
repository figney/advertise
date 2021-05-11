<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserRechargeOrder;
use App\Services\TaskService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RechargeHookHandle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(protected User $user, protected UserRechargeOrder $userRechargeOrder)
    {

    }


    public function handle()
    {
        TaskService::make()->rechargeHookHandle($this->user, $this->userRechargeOrder);
    }
}
