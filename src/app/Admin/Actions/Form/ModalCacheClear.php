<?php

namespace App\Admin\Actions\Form;

use Dcat\Admin\Actions\Response;
use Dcat\Admin\Form\AbstractTool;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Runner\Exception;

class ModalCacheClear extends AbstractTool
{
    /**
     * @return string
     */
	protected $title = '对话框';

    /**
     * Handle the action request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request)
    {

        try {
            Artisan::call('modelCache:clear');
            return $this->response()
                ->success('清理成功');
        } catch (Exception $e) {
            return $this->response()
                ->success('清理失败');
        }
    }

    /**
     * @return string|void
     */
    protected function href()
    {
        // return admin_url('auth/users');
    }

    /**
	 * @return string|array|void
	 */
	public function confirm()
	{
		 return ['确定清理对话框吗？', '确定清理对话框缓存吗？'];
	}

    /**
     * @param Model|Authenticatable|HasPermissions|null $user
     *
     * @return bool
     */
    protected function authorize($user): bool
    {
        return true;
    }

    /**
     * @return array
     */
    protected function parameters()
    {
        return [];
    }
}
