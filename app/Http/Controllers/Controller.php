<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    protected static $nonce = null;

    public static function nonce()
    {
        if (self::$nonce == null) {
            self::$nonce = base64_encode(random_bytes(9));
        }

        return self::$nonce;
    }
}
