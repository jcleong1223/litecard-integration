<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * The TikTok shop resolved by the ResolveShop ('shop') middleware.
     * Only available on routes inside the shop-scoped group.
     */
    protected function tiktokShop(Request $request): array
    {
        return $request->attributes->get('tiktok.shop');
    }
}
