<?php

namespace App\Admin\Repositories;

use App\Models\WalletLog as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class WalletLog extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
