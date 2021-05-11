<?php


namespace App\Models\Notifications;


use App\Enums\NotificationType;

/**
 * 测试通知
 * Class TestNotification
 * @package App\Models\Notifications
 */
class TestNotification extends BaseNotification implements INotification
{

    protected $fee;

    public bool $forced = true;
    public bool $socket = true;

    public $title_slug = "测试通知标题";
    public $content_slug = "测试通知内容";

    public $type = NotificationType::test;

    /**
     * TestNotification constructor.
     * @param $fee
     */
    public function __construct($fee)
    {
        $this->fee = $fee;
    }


    public function toArray(): array
    {
        return [

        ];
    }

    public function toParams(): array
    {
        return [
            'fee' => $this->fee
        ];
    }
}
