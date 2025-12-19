<?php

namespace App\Services;

class NetworkLogService
{
    public static function isNetworkLogEnabled(): bool
    {
        return (bool) config('logging.network_enabled', false);
    }
}
