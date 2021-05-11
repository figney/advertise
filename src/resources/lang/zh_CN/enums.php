<?php

use App\Enums\LanguageConfigType;
use App\Enums\OrderStatusType;
use App\Enums\PlatformType;
use App\Enums\ProductType;
use App\Enums\RechargeChannelType;
use App\Enums\TaskTargetType;
use App\Enums\TransferVoucherCheckType;
use App\Enums\UserHookType;
use App\Enums\WalletLogSlug;
use App\Enums\WalletLogType;
use App\Enums\WalletType;
use App\Enums\WithdrawChannelType;
use App\Enums\WithdrawOrderStatusType;

return [
    WalletLogType::class => [
        WalletLogType::DepositSystem => "系统加款",
        WalletLogType::WithdrawSystem => "系统扣款",
        WalletLogType::WithdrawUsdtToBalance => "USDT划转到余额",
        WalletLogType::DepositUsdtToBalance => "USDT划转到余额",
        WalletLogType::WithdrawBalanceToUsdt => "余额划转到USDT",
        WalletLogType::DepositBalanceToUsdt => "余额划转到USDT",
        WalletLogType::WithdrawWalletToMoneyBao => "钱包存入到赚钱宝",
        WalletLogType::DepositMoneyBaoToWallet => "赚钱宝取出到钱包",
        WalletLogType::DepositMoneyBaoInterestByBalance => "赚钱宝余额利息",
        WalletLogType::DepositMoneyBaoInterestByUSDT => "赚钱宝USDT利息",
        WalletLogType::DepositMoneyBaoInterestByGive => "赚钱宝赠送金利息",
        WalletLogType::WithdrawWalletToProduct => "钱包购买定期产品",
        WalletLogType::DepositProductToWallet => "定期产品到期返还到钱包",
        WalletLogType::DepositProductInterestByBalance => "定期产品现金利息",
        WalletLogType::DepositProductInterestByUSDT => "定期产品USDT利息",
        WalletLogType::DepositTransferVoucherRecharge => "银行卡转账充值",
        WalletLogType::DepositOnlinePayRecharge => "在线付款充值",
        WalletLogType::DepositUSDTRecharge => "USDT充值",
        WalletLogType::WithdrawUSDTWithdraw => "USDT提现",
        WalletLogType::WithdrawOnlineWithdraw => "网银提现",
        WalletLogType::DepositWithdrawErrorRefund => "提现失败退款",
        WalletLogType::DepositDaySignAward => "每日签到奖励",
        WalletLogType::DepositContinuousSignAward => "连续签到奖励",
        WalletLogType::DepositTotalSignAward => "累计签到奖励",
        WalletLogType::DepositRegisterAward => "新用户注册奖励",
        WalletLogType::DepositEveryInviteAward => "邀请好友奖励",
        WalletLogType::DepositTotalInviteAward => "累计邀请好友奖励",
        WalletLogType::DepositContinuousInviteAward => "连续邀请好友奖励",
        WalletLogType::DepositFirstRechargeAward => "首次充值奖励",
        WalletLogType::DepositEveryRechargeAward => "充值奖励",
        WalletLogType::DepositTotalRechargeAward => "累计充值奖励",
        WalletLogType::DepositContinuousRechargeAward => "连续充值奖励",
        WalletLogType::DepositContinuousIncreaseRechargeAward => "连续递增充值奖励",
        WalletLogType::DepositTotalEarningsAward => "累计收益奖励",
        WalletLogType::DepositContinuousEarningsAward => "连续收益奖励",
        WalletLogType::DepositFriendTaskAward => "下线完成任务奖励",
        WalletLogType::WithdrawDeductRechargeAward => "提现扣除赠送金",
        WalletLogType::WithdrawFriendDeductRechargeAward => "下线提现扣除上级奖励的赠送金",
        WalletLogType::DepositFriendBuyProductCommission => "下线购买产品佣金",
        WalletLogType::WithdrawBuyVip => "购买VIP",
        WalletLogType::DepositFriendBuyVipCommission => "下线购买VIP佣金",
    ],
    WalletType::class => [
        WalletType::balance => "余额",
        WalletType::usdt => "USDT",
        WalletType::give => "赠送金",
    ],
    WalletLogSlug::class => [
        WalletLogSlug::other => "其他",
        WalletLogSlug::recharge => "充值",
        WalletLogSlug::withdraw => "提现",
        WalletLogSlug::interest => "利息",
        WalletLogSlug::award => "奖励",
        WalletLogSlug::commission => "佣金",
        WalletLogSlug::deduct => "扣除",
        WalletLogSlug::transform => "转换",
        WalletLogSlug::deposit => "存入",
        WalletLogSlug::takeOut => "取出",
        WalletLogSlug::refund => "退款",
        WalletLogSlug::buy => "购买",
    ],
    LanguageConfigType::class => [
        LanguageConfigType::serve => "服务端",
        LanguageConfigType::client => "客户端",
        LanguageConfigType::default => "其他",
    ],
    ProductType::class => [
        ProductType::balance => "余额",
        ProductType::usdt => "USDT",

    ],
    RechargeChannelType::class => [
        RechargeChannelType::USDT_TRC20 => "USDT_TRC20",
        RechargeChannelType::OnLine => "在线支付",
        RechargeChannelType::TransferAccounts => "银行卡转账",
    ],
    TransferVoucherCheckType::class => [
        TransferVoucherCheckType::UnderReview => "审核中",
        TransferVoucherCheckType::Reject => "已驳回",
        TransferVoucherCheckType::Pass => "已入账",
    ],
    WithdrawChannelType::class => [
        WithdrawChannelType::USDT_TRC20 => "USDT_TRC20",
        WithdrawChannelType::OnLine => "线上自动",
    ],
    OrderStatusType::class => [
        OrderStatusType::Paying => "支付中",
        OrderStatusType::PayError => "支付失败",
        OrderStatusType::PaySuccess => "支付成功",
        OrderStatusType::Close => "关闭",
    ],
    PlatformType::class => [
        PlatformType::PayTM => "PayTM",
        PlatformType::IPayIndian => "IPayIndian",
        PlatformType::LaoSun => "老孙",
        PlatformType::FPay => "FPay",
        PlatformType::Yudrsu => "Yudrsu",
        PlatformType::Other => "其他",
    ],
    WithdrawOrderStatusType::class => [
        WithdrawOrderStatusType::Checking => "审核中",
        WithdrawOrderStatusType::CheckSuccess => "审核通过",
        WithdrawOrderStatusType::CheckError => "审核失败",
        WithdrawOrderStatusType::Close => "订单关闭",
        WithdrawOrderStatusType::CheckErrorAndRefund => "失败并退款",
        WithdrawOrderStatusType::Paying => "打款中",
        WithdrawOrderStatusType::PayError => "打款失败",
    ],
    UserHookType::class => [
        UserHookType::Register => "注册",
        UserHookType::Login => "登录",
        UserHookType::Sign => "签到",
        UserHookType::Invite => "邀请",
        UserHookType::Recharge => "充值",
        UserHookType::Withdraw => "提现",
        UserHookType::Earnings => "利息收益",
        UserHookType::BuyProduct => "购买产品",
    ],
    TaskTargetType::class => [
        TaskTargetType::First => "首次",
        TaskTargetType::Every => "每次",
        TaskTargetType::Accomplish => "累计/到达",
        TaskTargetType::ContinuousDay => "连续N天",
        TaskTargetType::ContinuousDayIncrease => "连续N天递增",
    ]
];
