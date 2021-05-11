<?php

namespace App\Models\Notifications;

interface INotification
{

    public function toArray(): array;

    public function toParams(): array;
}
