<?php

namespace App\Admin\Repositories;

use App\Models\MoneyBao as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class MoneyBao extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
