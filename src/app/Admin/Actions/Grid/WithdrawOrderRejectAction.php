<?php

namespace App\Admin\Actions\Grid;

use App\Admin\Forms\WithdrawOrderRejectForm;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Widgets\Modal;

class WithdrawOrderRejectAction extends RowAction
{

    protected $title = '拒绝通过';


    public function render()
    {


        return Modal::make()->lg()->body(WithdrawOrderRejectForm::make()->payload(['id' => $this->getKey()]))->title($this->title)->button("<button class='btn btn-danger btn-sm margin-lr-xs'>{$this->title}</button>");
    }
}
