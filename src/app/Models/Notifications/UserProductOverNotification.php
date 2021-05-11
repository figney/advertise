<?php


namespace App\Models\Notifications;


use App\Http\Resources\TaskNotificaltionResource;
use App\Models\Task;
use App\Models\UserProduct;
use Illuminate\Support\Str;


class UserProductOverNotification extends BaseNotification implements INotification
{


    public bool $forced = true;
    public bool $socket = true;

    public $title_slug = "产品到期通知";
    public $content_slug = "产品到期通知内容";

    public $type = "UserProductOverNotification";


    public function __construct(protected UserProduct $userProduct)
    {

    }


    public function toArray(): array
    {
        return [];
    }

    public function toParams(): array
    {
        return [
            'amount' => (float)$this->userProduct->amount,
            'day_cycle' => (float)$this->userProduct->day_cycle,
            'year_rate' => (float)$this->userProduct->day_rate * 365,
        ];
    }
}
