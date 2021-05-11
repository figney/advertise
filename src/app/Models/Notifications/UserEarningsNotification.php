<?php


namespace App\Models\Notifications;


use App\Enums\NotificationType;
use App\Enums\WalletType;
use App\Http\Resources\WalletLogResource;
use App\Models\User;
use App\Models\WalletLog;
use Illuminate\Support\Str;

/**
 * 收益到账通知
 * Class TestNotification
 * @package App\Models\Notifications
 */
class UserEarningsNotification extends BaseNotification implements INotification
{


    public bool $forced = false;
    public bool $socket = true;

    public $title_slug = "收益到账通知";
    public $content_slug = "收益到账通知内容";

    public $type = "UserEarningsNotification";


    public function __construct(protected WalletLog $walletLog, protected User $user)
    {

        if ($this->walletLog->fee >= 100 && $this->walletLog->wallet_type == WalletType::balance) {
            $this->forced = true;
        }
        if ($this->walletLog->fee >= 1 && $this->walletLog->wallet_type == WalletType::usdt) {
            $this->forced = true;
        }

    }


    public function toArray(): array
    {
        return json_decode(WalletLogResource::make($this->walletLog)->toJson(), true);
    }

    public function toParams(): array
    {
        return [
            'fee' => $this->walletLog->fee,
            'wallet_type_lang' => $this->walletLog->wallet_type,
            'action_type_lang' => $this->walletLog->action_type,
        ];
    }
}
