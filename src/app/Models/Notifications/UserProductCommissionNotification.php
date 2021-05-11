<?php


namespace App\Models\Notifications;


use App\Http\Resources\TaskNotificaltionResource;
use App\Models\Task;
use App\Models\UserProduct;
use Illuminate\Support\Str;


class UserProductCommissionNotification extends BaseNotification implements INotification
{


    public bool $forced = true;
    public bool $socket = true;

    public $title_slug = "产品佣金到账通知";
    public $content_slug = "产品佣金到账通知内容";

    public $type = "UserProductCommissionNotification";


    public function __construct(protected float $fee, protected int $level, protected UserProduct $userProduct)
    {

    }


    public function toArray(): array
    {
        return [];
    }

    public function toParams(): array
    {
        return [
            'fee' => $this->fee,
            'level' => $this->level,
            'wallet_type_lang' => $this->userProduct->product->type,
            'amount' => (float)$this->userProduct->amount,
        ];
    }
}
