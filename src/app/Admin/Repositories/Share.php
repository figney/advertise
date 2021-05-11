<?php

namespace App\Admin\Repositories;

use App\Models\Share as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Share extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
