<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class RepairUserCountData extends Command
{

    protected $signature = 'command:RepairUserCountData';


    protected $description = '修复用户统计数据';


    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        //修复赚钱宝赠送金收益
        User::query()->with(['moneyBao', 'walletCount'])->chunk(30, function ($users) {
            /**@var User $user */
            foreach ($users as $user) {
                $user->moneyBao->update([
                    'give_balance_earnings' => $user->walletCount->give_balance_earnings
                ]);
            }
        });
    }
}
