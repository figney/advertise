<?php


namespace App\Admin\Actions\Grid;


use App\Admin\Renderable\UserWalletLogTable;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Widgets\Modal;

class UserWalletLogAction extends RowAction
{
    /**
     * @return string
     */
    protected $title = '钱包明细';

    protected $user_id;


    public function __construct(int $user_id)
    {
        parent::__construct($this->title);

        $this->user_id = $user_id;

    }


    public function render()
    {


        $form = UserWalletLogTable::make()->payload(['user_id' => $this->user_id]);


        return Modal::make()->xl()->body($form)->title($this->title)->button($this->title);
    }

}
