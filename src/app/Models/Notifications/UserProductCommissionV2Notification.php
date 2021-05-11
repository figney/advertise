<?php


namespace App\Models\Notifications;


use App\Http\Resources\TaskNotificaltionResource;
use App\Models\Task;
use App\Models\User;
use App\Models\UserProduct;
use Illuminate\Support\Str;


class UserProductCommissionV2Notification extends BaseNotification implements INotification
{


    public bool $forced = true;
    public bool $socket = true;

    public $title_slug = "产品佣金到账通知";
    public $content_slug = "产品佣金到账通知内容";

    public $type = "UserProductCommissionV2Notification";


    public function __construct(
        protected float $fee,
        protected float $all_fee,
        protected bool $is_no_commission,
        protected bool $is_get_all_commission,
        protected bool $is_buy_product,
        protected int $level,
        protected User $buyUser,
        protected UserProduct $userProduct,
        protected float $my_product_buy_amount,
        protected float $my_product_zhu_amount,
    )
    {

        if ($this->is_no_commission) {
            $this->title_slug = "产品佣金无法获得通知";
            $this->content_slug = "产品佣金无法获得通知内容";
        }

        if (!$this->is_get_all_commission && !$this->is_no_commission) {
            $this->title_slug = "产品佣金部分到账通知";
            $this->content_slug = "产品佣金部分到账通知内容";
        }

    }


    public function toArray(): array
    {
        return [];
    }

    public function toParams(): array
    {
        $my_product_amount = $this->is_buy_product ? $this->my_product_buy_amount : $this->my_product_zhu_amount;

        return [
            'fee' => MoneyFormat($this->fee),//获得的佣金 0
            'all_fee' => MoneyFormat($this->all_fee),//总佣金 1
            'level' => $this->level,//下线层级 2
            'my_product_amount' => MoneyFormat($my_product_amount),//我持有产品的总额 3
            'buy_user_name' => $this->buyUser->name,//下级的昵称 4
            'buy_amount' => MoneyFormat($this->userProduct->amount),//下级购买产品的金额 5
            'is_buy_product' => $this->is_buy_product,//是否是购买类型，否则是租用 6
            'is_buy_product_lang' => $this->is_buy_product ? "购买" : "租用", //7
            'is_no_commission' => $this->is_no_commission,//是否未能获得佣金 8
            'is_get_all_commission' => $this->is_get_all_commission,//是否能获得全部佣金 9
            'my_product_zhu_amount' => $this->my_product_zhu_amount,//我租用的产品 10
            'my_product_buy_amount' => MoneyFormat($this->my_product_buy_amount),//我购买产品的总额 11
        ];
    }
}
