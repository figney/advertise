<?php


namespace App\Admin\Metrics;


use App\Models\DeviceLog;
use Carbon\Carbon;
use Dcat\Admin\Widgets\Metrics\Card;
use Illuminate\Http\Request;

class MetricDeviceLog extends Card
{


    protected function init()
    {
        parent::init();
        $this->title('设备行为轨迹统计');

        $this->dropdown([
            'today' => '今日',
            'yesterday' => '昨日',
            'all' => '全部',
        ]);
    }

    public function handle(Request $request)
    {
        $option = $request->get('option', 'today');

        $pages_html = "";


        $counts = collect();


        $obj = DeviceLog::query()->where('event_name', 'Open page')->groupBy('untitled_page')->select(['untitled_page', \DB::raw("count(*) as count")]);
        if ($option == "today") $obj->where('created_at', '>', Carbon::today());
        if ($option == "yesterday") $obj->whereBetween('created_at', [Carbon::yesterday(), Carbon::yesterday()->endOfDay()]);

        $list = $obj->get();

        foreach ($list as $item) {
            $counts->add(['page' => $item->untitled_page, 'count' => $item->count]);
        }


        $counts->sortByDesc('count')->each(function ($item) use (&$pages_html) {
            $pages_html .= "<div class='padding-top-sm'>" . data_get(self::Pages, $item['page'], $item['page']) . "：" . $item['count'] . "</div>";
        });

        $content = <<<HTML
<div class="padding-lr padding-bottom">
<div>
<div class="fs-20">页面访问统计</div>
<div>$pages_html</div>
</div>
</div>
HTML;


        $this->content($content);

    }

    public function renderContent()
    {
        parent::renderContent();

        return $this->content;

    }

    const Pages = [
        "HomeIndex" => "主页",
        "HomeMoney" => "投资页",
        "HomeTask" => "奖励页",
        "HomeTeam" => "团队页",
        "HomeUser" => "个人中心",
        "About" => "关于我们",
        "Security" => "安全中心",
        "UpdateLoginpwd" => "修改登录密码",
        "Contact" => "联系客服页",
        "Notification" => "通知页",
        "TeamList" => "团队列表页",
        "FriendList" => "好友列表",
        "InvestHistory" => "投资记录",
        "FriendDepositHistory" => "好友贡献历史",
        "WithdrawHistory" => "提现记录",
        "DepositHistory" => "充值记录",
        "Deposit" => "充值页",
        "Withdraw" => "提现页",
        "BalanceTransfer" => "余额转换",
        "InvestDetail" => "投资详情",
        "Language" => "修改语言",
        "Wallet" => "我的钱包页",
        "WalletHistory" => "钱包流水",
        "Invest" => "我的投资",
        "BaoWithdraw" => "取出赠送金",
        "BaoDeposit" => "增加算力页面",
        "TransferHistory" => "划转记录",
        "SubmitCertifications" => "提交银行卡转款材料",
        "Order" => "订单提交成功",
    ];

}
