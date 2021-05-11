<?php

namespace App\Admin\Actions\Grid;

use App\Admin\Forms\SetUserWallet;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Widgets\Modal;

class UserWallet extends RowAction
{
    /**
     * @return string
     */
    protected $title = '余额操作';

    public function __construct(protected int $user_id)
    {
        parent::__construct($this->title);


    }


    public function render()
    {


        $form = SetUserWallet::make()->payload(['user_id' => $this->user_id]);

        return Modal::make()->lg()->body($form)->title($this->title)->button($this->title);
    }

}
