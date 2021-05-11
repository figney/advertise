<?php 
return [
    'labels' => [
        'UserTransferVoucher' => 'UserTransferVoucher',
        'user-transfer-voucher' => 'UserTransferVoucher',
    ],
    'fields' => [
        'user_id' => '用户ID',
        'channel_item_id' => '转账渠道收款卡ID',
        'image' => '转账凭证图片',
        'image_md5' => '转账凭证图片MD5',
        'user_name' => '转账人名称',
        'card_number' => '转账卡号',
        'bank_name' => '银行名称',
        'amount' => '转账金额',
        'time' => '转账时间',
        'status' => '是否通过',
        'check_type' => '审核类型',
        'check_slug' => '审核备注语言key',
        'check_time' => '审核时间',
        'wallet_log_id' => '入账流水记录',
    ],
    'options' => [
    ],
];
