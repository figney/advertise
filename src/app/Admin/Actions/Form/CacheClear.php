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

class CacheClear extends AbstractTool
{
    /**
     * @return string
     */
	protected $title = '缓存';

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
            Artisan::call('cache:clear');
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
		 return ['确定清理缓存吗？', '清除各种缓存、如页面、Redis、 配置文件等等！它会清空 Redis 数据库的全部数据，谨慎操作！！！'];
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
