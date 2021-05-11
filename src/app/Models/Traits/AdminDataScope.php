<?php


namespace App\Models\Traits;


use App\Admin\Controllers\Base;
use App\Models\User;
use Illuminate\Database\Query\Builder;

trait AdminDataScope
{
    use Base;

    /**
     * @param Builder $query
     * @return mixed
     */
    public function scopeNoTester($query, $tester = true)
    {
        if (!$tester) return $query;
        if ($this->getTable() === "users") return $query->whereNotIn('id', User::testerIds());
        return $query->whereNotIn('user_id', User::testerIds());
    }

    /**
     *
     * @param Builder $query
     * @param null $link_id
     * @param bool $tester
     * @return mixed
     */
    public function scopeByChannel($query, $link_id = null, $tester = true)
    {
        if (!$this->isAdministrator()) {
            if (!$link_id) return $query->whereIn('channel_id', $this->getChannelIds())->noTester($tester);

            return $query->whereIn('link_id', $link_id)->noTester($tester);
        }


        return $query->noTester($tester);
    }


}
