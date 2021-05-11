<?php

namespace App\Admin\Repositories;

use App\Models\RechargeChannelList as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class RechargeChannelList extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
