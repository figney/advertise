<?php

namespace App\Admin\Repositories;

use App\Models\Sms as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Sms extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
