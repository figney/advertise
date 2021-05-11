<?php

namespace App\Jobs;

use App\Services\TaskService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InviteHookHandle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(protected int $user_id, protected int $invite_user_id, protected int $level)
    {
        //
    }


    public function handle()
    {
        TaskService::make()->inviteHookHandle($this->user_id, $this->invite_user_id, $this->level);
    }
}
