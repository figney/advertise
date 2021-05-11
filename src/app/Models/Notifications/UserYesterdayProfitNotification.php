<?php


namespace App\Models\Notifications;


class UserYesterdayProfitNotification extends BaseNotification implements INotification
{

    public bool $forced = true;
    public bool $socket = true;

    public $title_slug = "昨日收益通知";
    public $content_slug = "昨日收益通知内容";

    public $type = "UserYesterdayProfitNotification";


    public function __construct(protected float $profit, protected float $commission)
    {
    }

    public function toArray(): array
    {
        return [];
    }

    public function toParams(): array
    {
        return [
            'profit' => $this->profit,
            'commission' => $this->commission,
        ];
    }
}
