<?php


namespace App\Traits;


use App\Enums\QueueType;
use App\Http\Resources\NotificationResource;
use App\Jobs\SocketIoToUser;
use App\Models\Notification;
use App\Models\Notifications\BaseNotification;
use App\Models\User;
use Carbon\Carbon;


trait UserNotifiable
{

    /**
     * @param BaseNotification $instance
     */
    public function notify($instance)
    {
        /** @var User $user */
        $user = $this;

        $notification = Notification::query()->create([
            'user_id' => $user->id,
            'type' => $instance->type,
            'socket' => $instance->socket,
            'forced' => $instance->forced,
            'title_slug' => $instance->title_slug,
            'content_slug' => $instance->content_slug,
            'params' => $instance->toParams(),
            'data' => $instance->toArray(),
            'read_time' => now(),
            'is_read' => false,
        ]);

        if ($notification->socket) {

            try {
                if (Carbon::make($user->last_active_at)->gt(now()->addMinutes(-60))) {
                    $notification->local = $user->local;
                    $data = json_decode(NotificationResource::make($notification)->toJson(), true);
                    $data['notifications_count'] = $user->unreadNotifications()->count();
                    dispatch(new SocketIoToUser($user, 'notification', $data))->onQueue(QueueType::send);
                }
            } catch (\Exception $exception) {
                \Log::warning("notify 失败：" . $exception->getMessage());
            }
        }

    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\Jenssegers\Mongodb\Relations\HasMany
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class)->orderBy('created_at', 'desc');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\Jenssegers\Mongodb\Relations\HasMany
     */
    public function readNotifications()
    {
        return $this->notifications()->read();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\Jenssegers\Mongodb\Relations\HasMany
     */
    public function unreadNotifications()
    {
        return $this->notifications()->unread();
    }

}
