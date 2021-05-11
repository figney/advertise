<?php


namespace App\Models\Notifications;


use App\Http\Resources\TaskNotificaltionResource;
use App\Models\Task;
use Illuminate\Support\Str;

/**
 * 测试通知
 * Class TestNotification
 * @package App\Models\Notifications
 */
class UserAwardNotification extends BaseNotification implements INotification
{


    public bool $forced = true;
    public bool $socket = true;

    public $title_slug = "奖励到账通知";
    public $content_slug = "奖励到账通知内容";

    public $type = "UserAwardNotification";

    /**
     * TestNotification constructor.
     * @param $fee
     */
    public function __construct(protected float $fee, protected string $type_title, protected Task $task)
    {
        $this->forced = $this->task->is_show_alert;
    }


    public function toArray(): array
    {
        return [];
    }

    public function toParams(): array
    {
        return [
            'fee' => $this->fee,//奖励金额
            'type_title_lang' => $this->type_title,
            'wallet_type_lang' => $this->task->wallet_type,
            'hook_lang' => $this->task->hook,
            'task_target_lang' => $this->task->task_target,
        ];
    }
}
