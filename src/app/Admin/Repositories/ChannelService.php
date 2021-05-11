<?php

namespace App\Admin\Repositories;

use App\Models\ChannelService as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class ChannelService extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
