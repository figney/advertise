<?php

namespace App\Jobs;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SocketIoToUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(protected User $user, protected string $event, protected array $data)
    {

    }


    public function handle()
    {
        $push_url = Setting('socket_url') . '/push/toUser';

        $data['event'] = $this->event;
        $data['data'] = $this->data;
        $data['id'] = $this->user->hash;
        $user_info = UserService::make()->getUserInfo($this->user);
        $data['data']['user_info'] = json_decode(UserResource::make($user_info)->toJson(), true);

        \Http::post($push_url, $data);

    }
}
