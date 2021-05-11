<?php

namespace App\Models;

use App\Enums\UserAdTaskType;
use App\Services\ShareService;
use Carbon\Carbon;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Builder;


class UserAdTask extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'user_ad_tasks';

    protected $connection = "mysql";

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function logs()
    {
        return $this->hasMany(UserAdTaskLog::class);
    }

    public function adTask()
    {
        return $this->belongsTo(AdTask::class);
    }

    public function getAdTaskUrl()
    {
        $shareService = new ShareService();

        $domain = $shareService->getDomain();
        if ($domain) {
            $url = \Str::finish($domain->domain, '/');
            $url .= "at?uat=" . $this->id;
            return $url;
        }
        return "NO URL";
    }


    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return mixed
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', UserAdTaskType::InProgress)->where('expired_time', '>=', now());
    }

    public function scopeFinished($query)
    {
        return $query->where('status', UserAdTaskType::Finished);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return mixed
     */
    public function scopeToday($query)
    {
        return $query->where('created_at', '>=', Carbon::today());
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return mixed
     */
    public function scopeExpiredCount($query)
    {
        return $query
            ->where('overdue_return', 0)
            ->where('expired_time', '<', now())
            ->whereIn('status', [UserAdTaskType::InProgress, UserAdTaskType::HasExpired]);
    }
}
