<?php

namespace App\Admin\Actions\Grid;

use App\Admin\Forms\TransferVoucherRejectForm;
use App\Models\UserTransferVoucher;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Widgets\Modal;

class TransferVoucherRejectAction extends RowAction
{

    protected $title = '驳回操作';

    protected $userTransferVoucher;

    public function __construct(UserTransferVoucher $userTransferVoucher)
    {
        parent:: __construct($this->title);

        $this->userTransferVoucher = $userTransferVoucher;
    }


    public function render()
    {


        return Modal::make()->lg()->body(TransferVoucherRejectForm::make()->payload(['id' => $this->getKey()]))->title($this->title)->button("<span class='mr-1 text-danger'>{$this->title}</span>");
    }

}
