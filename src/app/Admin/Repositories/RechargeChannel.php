<?php

namespace App\Admin\Repositories;

use App\Models\RechargeChannel as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class RechargeChannel extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
