<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

/**
 * 钱包明细类型
 * Class WalletLogType
 * @package App\Enums
 */
final class WalletLogType extends Enum implements LocalizedEnum
{
    /**
     * 系统加款，收入
     */
    const DepositSystem = "DepositSystem";
    /**
     * 系统扣款，支出
     */
    const WithdrawSystem = "WithdrawSystem";

    /**
     * USDT转出到余额，USDT支出
     */
    const WithdrawUsdtToBalance = "WithdrawUsdtToBalance";

    /**
     * USDT转出到余额，余额收入
     */
    const DepositUsdtToBalance = "DepositUsdtToBalance";

    /**
     * 余额转出到USDT，余额支出
     */
    const WithdrawBalanceToUsdt = "WithdrawBalanceToUsdt";

    /**
     * 余额转出到USDT，USDT收入
     */
    const DepositBalanceToUsdt = "DepositBalanceToUsdt";


    /**
     * 钱包 转出 到 赚钱宝，钱包支出
     */
    const WithdrawWalletToMoneyBao = "WithdrawWalletToMoneyBao";

    /**
     * 赚钱宝 转出 到 钱包，钱包收入
     */
    const DepositMoneyBaoToWallet = "DepositMoneyBaoToWallet";


    /**
     * 赚钱宝现金利息
     */
    const DepositMoneyBaoInterestByBalance = "DepositMoneyBaoInterestByBalance";

    /**
     * 赚钱宝USDT利息
     */
    const DepositMoneyBaoInterestByUSDT = "DepositMoneyBaoInterestByUSDT";

    /**
     * 赚钱宝赠送金利息
     */
    const DepositMoneyBaoInterestByGive = "DepositMoneyBaoInterestByGive";


    /**
     * 钱包 转出 到 理财产品，钱包支出
     */
    const WithdrawWalletToProduct = "WithdrawWalletToProduct";

    /**
     * 理财产品 转出 到 钱包，钱包收入
     */
    const DepositProductToWallet = "DepositProductToWallet";

    /**
     * 理财产品现金利息
     */
    const DepositProductInterestByBalance = "DepositProductInterestByBalance";

    /**
     * 理财产品USDT利息
     */
    const DepositProductInterestByUSDT = "DepositProductInterestByUSDT";


    /**
     * 银行卡转账充值
     */
    const DepositTransferVoucherRecharge = "DepositTransferVoucherRecharge";


    /**
     * 在线付款充值
     */
    const DepositOnlinePayRecharge = "DepositOnlinePayRecharge";

    /**
     * USDT充值
     */
    const DepositUSDTRecharge = "DepositUSDTRecharge";


    /**
     * USDT提现
     */
    const WithdrawUSDTWithdraw = "WithdrawUSDTWithdraw";

    /**
     * 在线银行提现
     */
    const WithdrawOnlineWithdraw = "WithdrawOnlineWithdraw";

    /**
     * 提现失败退款
     */
    const DepositWithdrawErrorRefund = "DepositWithdrawErrorRefund";


    /**
     * 每日签到奖励
     */
    const DepositDaySignAward = "DepositDaySignAward";

    /**
     * 连续签到奖励
     */
    const DepositContinuousSignAward = "DepositContinuousSignAward";

    /**
     * 累计签到奖励
     */
    const DepositTotalSignAward = "DepositTotalSignAward";

    /**
     * 新用户注册奖励
     */
    const DepositRegisterAward = "DepositRegisterAward";


    /**
     * 每次邀请好友奖励
     */
    const DepositEveryInviteAward = "DepositEveryInviteAward";

    /**
     * 累计邀请好友奖励
     */
    const DepositTotalInviteAward = "DepositTotalInviteAward";


    /**
     * 连续邀请好友奖励
     */
    const DepositContinuousInviteAward = "DepositContinuousInviteAward";

    /**
     * 首次充值奖励
     */
    const DepositFirstRechargeAward = "DepositFirstRechargeAward";

    /**
     * 充值奖励
     */
    const DepositEveryRechargeAward = "DepositEveryRechargeAward";

    /**
     * 累计充值奖励
     */
    const DepositTotalRechargeAward = "DepositTotalRechargeAward";
    /**
     * 连续充值奖励
     */
    const DepositContinuousRechargeAward = "DepositContinuousRechargeAward";
    /**
     * 连续递增充值奖励
     */
    const DepositContinuousIncreaseRechargeAward = "DepositContinuousIncreaseRechargeAward";
    /**
     * 累计收益奖励
     */
    const DepositTotalEarningsAward = "DepositTotalEarningsAward";
    /**
     * 连续收益奖励
     */
    const DepositContinuousEarningsAward = "DepositContinuousEarningsAward";

    /**
     * 下线完成任务奖励
     */
    const DepositFriendTaskAward = "DepositFriendTaskAward";

    /**
     * 提现扣除充值赠送金
     */
    const WithdrawDeductRechargeAward = "WithdrawDeductRechargeAward";

    /**
     * 下线提现扣除上级奖励的赠送金
     */
    const WithdrawFriendDeductRechargeAward = "WithdrawFriendDeductRechargeAward";


    /**
     * 下线购买产品佣金
     */
    const DepositFriendBuyProductCommission = "DepositFriendBuyProductCommission";


    /**
     * 开通VIP
     */
    const WithdrawBuyVip = "WithdrawBuyVip";

    /**
     * 下线购买VIP佣金
     */
    const DepositFriendBuyVipCommission = "DepositFriendBuyVipCommission";


    /**
     * 广告任务完成利息
     */
    const DepositAdTaskInterestByBalance = "DepositAdTaskInterestByBalance";

    /**
     * 下级完成广告任务佣金
     */
    const DepositFriendAdTaskCommission = "DepositFriendAdTaskCommission";

}
