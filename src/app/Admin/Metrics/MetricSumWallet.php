<?php


namespace App\Admin\Metrics;


use App\Enums\OrderStatusType;
use App\Enums\WalletType;
use App\Enums\WithdrawOrderStatusType;
use App\Models\UserRechargeOrder;
use App\Models\UserWithdrawOrder;
use Dcat\Admin\Widgets\Metrics\SingleRound;

class MetricSumWallet extends SingleRound
{
    public function init()
    {
        parent::init();

        $this->title('充提金额统计');
        $this->subTitle('全部');
        $this->contentWidth(0, 12);
    }

    public function fill()
    {

        //充值
        $balance_sum = UserRechargeOrder::query()->where('wallet_type', WalletType::balance)->where('order_status', OrderStatusType::PaySuccess)->byChannel()->sum('actual_amount');
        $usdt_sum = UserRechargeOrder::query()->where('wallet_type', WalletType::usdt)->where('order_status', OrderStatusType::PaySuccess)->byChannel()->sum('actual_amount');
        $bu_sum = $balance_sum + UsdtToBalance($usdt_sum);
        $zcz = collect([$balance_sum, $usdt_sum, $bu_sum])->map(fn($v) => round($v, 2))->toArray();
        //提现
        $t_balance_sum = UserWithdrawOrder::query()->where('wallet_type', WalletType::balance)->where('order_status', WithdrawOrderStatusType::CheckSuccess)->byChannel()->sum('actual_amount');
        $t_usdt_sum = UserWithdrawOrder::query()->where('wallet_type', WalletType::usdt)->where('order_status', WithdrawOrderStatusType::CheckSuccess)->byChannel()->sum('actual_amount');
        $t_bu_sum = $t_balance_sum + UsdtToBalance($t_usdt_sum);
        $ztx = collect([$t_balance_sum, round($t_usdt_sum), $t_bu_sum])->map(fn($v) => round($v, 2))->toArray();

        $series = [$bu_sum <= 0 ? $bu_sum : round($t_bu_sum / $bu_sum * 100, 2)];

        $this->withChart($series);

        $this->withFooter($zcz, $ztx, round($bu_sum - $t_bu_sum, 2));
    }

    public function render()
    {
        $this->fill();

        return parent::render();
    }

    public function withChart($series)
    {
        return $this->chart([
            'series' => $series,
        ]);
    }

    public function withFooter($zcz, $ztx, $lr)
    {
        $fh = Setting('fiat_code');

        $lr = ShowMoney($lr);

        return $this->footer(
            <<<HTML
<div class="row text-center" style="width: 100%">
  <div class="col-4 border-top border-right padding-tb-sm">
      <div class="mb-50">充值</div>
      <div class="fs-12">总数：$zcz[2] $fh</div>
      <div class="fs-12">法币：$zcz[0] $fh</div>
      <div class="fs-12">数字：$zcz[1] USDT</div>
  </div>
  <div class="col-4 border-top border-right padding-tb-sm">
      <div class="mb-50">提现</div>
      <div class="fs-12">总数：$ztx[2] $fh</div>
      <div class="fs-12">法币：$ztx[0] $fh</div>
      <div class="fs-12">数字：$ztx[1] USDT</div>
  </div>
  <div class="col-4 border-top padding-tb-sm">
      <div class="mb-50">利润</div>
      <div class="fs-14">$lr</div>
  </div>
</div>
HTML
        );
    }
}
