<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\TaskService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SignHookHandle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(protected User $user)
    {
        //
    }


    public function handle()
    {
        TaskService::make()->signHookHandle($this->user);
    }
}
