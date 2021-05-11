<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SocketIoToAll implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected array $data;
    protected string $event;


    public function __construct($event, $data)
    {
        $this->event = $event;
        $this->data = $data;
    }

    public function handle()
    {
        $push_url = Setting('socket_url') . '/push/toAll';

        $data['event'] = $this->event;
        $data['data'] = $this->data;

        \Http::post($push_url, $data);

    }
}
