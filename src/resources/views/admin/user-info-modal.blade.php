@php
    /** @var \App\Models\User $user ****/

$col = 3;
@endphp
<div class="container user-info">
    <h4>基本信息</h4>
    <div class="row">
        <div class="item col-{{$col}}">ID：{{$user->id}}</div>
        <div class="item col-{{$col}}">昵称：{{$user->name}}</div>
        <div class="item col-{{$col}}">手机号码：{{$user->national_number}}</div>
        <div class="item col-{{$col}}">渠道：{{$user->channel?->name}}</div>
        <div class="item col-{{$col}}">注册深度：{{$user->invite->level}}</div>
        <div class="item col-{{$col}}">总下线：{{$user->invite->total_all}}</div>
    </div>
    <h4 class="mt-1">钱包信息</h4>
    <div class="row">
        <div class="item col-{{$col}}">提现次数：{{$user->withdraw_count}}</div>
        <div class="item col-{{$col}}">充值次数：{{$user->recharge_count}}</div>
        <div class="item col-{{$col}}">余额：{{(float)$user->wallet->balance}}</div>
        <div class="item col-{{$col}}">usdt_余额：{{(float)$user->wallet->usdt_balance}}</div>
        <div class="item col-{{$col}}">赠送金_余额：{{(float)$user->wallet->give_balance}}</div>
        <div class="item col-{{$col}}">余额总提现：{{(float)$user->walletCount->balance_withdraw}}</div>
        <div class="item col-{{$col}}">余额总充值：{{(float)$user->walletCount->balance_recharge}}</div>
        <div class="item col-{{$col}}">余额总收益：{{(float)$user->walletCount->balance_earnings}}</div>
    </div>
    <h4 class="mt-1">矿机信息</h4>
    <div class="row">
        <div class="item col-{{$col}}">进行中：{{(float) $user->products()->where('is_over',0)->sum('amount')}}</div>
    </div>
    <h4 class="mt-1">邀请信息</h4>
    <div class="row">

        @for($i=1;$i<=10;$i++)
            <div class="item col-4">
                <span>{{$i}}级下线：{{data_get($user->invite,'total_'.$i)}}人</span>
                @if(data_get($user->invite,'total_'.$i)>0)
                    @php
                        $inviteData = $user->getInvite($i)->select([\DB::raw("sum(recharge_count) as recharge_count_sum"),\DB::raw("sum(balance_recharge) as balance_recharge_sum"),\DB::raw("sum(withdraw_count) as withdraw_count_sum"),\DB::raw("sum(balance_withdraw) as balance_withdraw_sum")])->first();
                    @endphp
                    <span>|</span>
                    <span>充值：{{$inviteData->recharge_count_sum}}次-{{(float)$inviteData->balance_recharge_sum}}</span>
                    <span>|</span>
                    <span>提现：{{$inviteData->withdraw_count_sum}}次-{{(float)$inviteData->balance_withdraw_sum}}</span>
                @endif
            </div>
        @endfor
    </div>
</div>
<style>
    .user-info .row .item {
        padding: 5px 15px;
    }
</style>
