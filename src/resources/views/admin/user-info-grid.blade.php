@php
    /** @var \App\Models\User|\Illuminate\Database\Query\Builder $user ****/
$relations = collect($user->getRelations())->keys()->toArray();
abort_if(!in_array('wallet',$relations),400,'请关联user.wallet');
abort_if(!in_array('walletCount',$relations),400,'请关联user.walletCount');
abort_if(!in_array('withdrawOrdersChecking',$relations),400,'请关联user.withdrawOrdersChecking');
abort_if(!in_array('vips',$relations),400,'请关联user.vips');
$hb = " ".Setting('fiat_code');
$usdt = " U";
@endphp
<div style="">

    <div>@if($user->tester)<span class="btn sm-btn btn-danger margin-bottom-xs">测试用户</span>@endif ID：{{$user->id}}
        - {{$user->created_at->diffForHumans()}}注册
    </div>
    <div>
        <span>渠道：{{$user->channel_id}}</span>
        <span class="margin-left">链接：{{$user->link_id}}</span>
    </div>
    @if($user->vips)
        @foreach($user->vips as $userVip)
            <div class="margin-top-xs">
                <span class="text-danger font-bold">VIP {{$userVip->level}}</span>
                <span class="margin-left-xs">消费:{!! ShowMoneyLine($userVip->buy_money_count) !!} | 次数:{{$userVip->task_num}} 叠加:{{$userVip->buy_number_count}}  </span>
            </div>
        @endforeach
    @endif
    <div style="margin-top: 5px;">
        <span>余额：</span>
        <span
            class="text-success">{{round($user->wallet->balance,2)}}<small>{{$hb}}</small> {{ShowRmb($user->wallet->balance)}}</span>
        |

        <span class="text-info">{{round($user->wallet->give_balance,4)}}<small> 赠</small></span>
    </div>
    <div style="margin-top: 5px;">
        <span>总收益：</span>
        <span
            class="text-success">{{round($user->walletCount->balance_earnings,2)}}<small>{{$hb}}</small> {{ShowRmb($user->walletCount->balance_earnings)}}</span>
    </div>

    <div style="margin-top: 5px;">
        <span>已充值：</span>
        <span class="text-danger">{{$user->recharge_count}}次</span> |
        <span
            title=""
            class="">{{(float)$user->walletCount->balance_recharge}}<small>{{$hb}}</small> {{ShowRmb($user->walletCount->balance_recharge)}}</span>
    </div>
    <div style="margin-top: 5px;">
        <span>已提现：</span>
        <span class="text-danger">{{$user->withdraw_count}}次</span> |
        <span
            class="">{{(float)$user->walletCount->balance_withdraw}}<small>{{$hb}}</small> {{ShowRmb($user->walletCount->balance_withdraw)}}</span>
    </div>
    <div style="margin-top: 5px;">
        <span>提现中：</span>

        <span class="text-danger">{{$user->withdrawOrdersChecking->count()}}次</span> |
        @php
            $txz = (float)$user->withdrawOrdersChecking->filter(fn($item)=>$item->wallet_type=== \App\Enums\WalletType::balance)->sum('amount');
        @endphp
        <span
            class="">{{$txz}}<small>{{$hb}}</small> {{ShowRmb($txz)}}</span>
    </div>
    <div style="margin-top: 5px;" class="flex">
        <div>{!! $model !!}</div>
        <div class="margin-left">{!! $logModel !!}</div>
    </div>
</div>

