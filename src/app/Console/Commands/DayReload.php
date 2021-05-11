<?php

namespace App\Console\Commands;

use App\Enums\QueueType;
use App\Jobs\SocketIoToAll;
use Illuminate\Console\Command;

class DayReload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:DayReload';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每天凌晨通知刷新';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        dispatch(new SocketIoToAll('reload', []))->onQueue(QueueType::allSend);
    }
}
