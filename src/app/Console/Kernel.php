<?php

namespace App\Console;

use App\Enums\QueueType;
use App\Jobs\GetUsdtToMoneyRate;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;


class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();


        //执行每日结算投资理财利息发放/产品结算操作
        /* $schedule->command('command:ProductGrantInterest')
             ->everyThreeMinutes()
             ->runInBackground()
             ->withoutOverlapping();*/
        //执行每日结算产品结束检测
        /*$schedule->command('command:CheckDayProductOver')
            ->everyTwoMinutes()
            ->runInBackground();*/
        //执行一次性产品结算操作
        /* $schedule->command('command:ProductOver')
             ->everyThreeMinutes()
             ->runInBackground()
             ->withoutOverlapping();*/


        //执行每日结算产品结束检测
        $schedule->command('command:AdTaskDispose')
                 ->everyMinute()
                 ->runInBackground()
                 ->withoutOverlapping();


        //获取usdt地址
        //$schedule->command('command:GetCoinAddress')->runInBackground()->everyMinute();

        //每天凌晨通知页面刷新
        $schedule->command('command:DayReload')->runInBackground()->daily();

        //每天清除数据
        $schedule->command('command:ClearData')->runInBackground()->dailyAt('04:00');

        //获取实时汇率
        /*$schedule->command("command:GetUsdtToMoneyRate")
            ->runInBackground()
            ->everyTenMinutes();*/
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
