<?php

namespace App\Console\Commands;

use App\Services\TikTok\TokenManager;
use App\Services\TikTok\Exceptions\TikTokApiException;
use App\Services\TikTok\Resources\AuthService;
use Illuminate\Console\Command;

class TiktokShopsSync extends Command
{
    protected $signature = 'tiktok:shops:sync
        {--access-token= : Access token obtained from /token/get}
        {--refresh-token= : Refresh token obtained from /token/get}
        {--merchant-ref= : Merchant reference to bind the shops to}
        {--access-expires-at= : Access token expiry as unix timestamp}
        {--refresh-expires-at= : Refresh token expiry as unix timestamp}
        {--scopes= : Comma-separated granted scopes}';

    protected $description = 'Fetch the authorized shops for a token set and persist everything for later API calls';

    public function handle(AuthService $authService, TokenManager $tokenManager): int
    {
        $accessToken = $this->option('access-token');
        $refreshToken = $this->option('refresh-token');

        if (! $accessToken) {
            $this->error('The --access-token option is required.');

            return self::FAILURE;
        }

        $this->info('Fetching authorized shops from TikTok...');

        try {
            $shops = $authService->getAuthorizedShops($accessToken)['shops'] ?? [];
        } catch (TikTokApiException $e) {
            $this->error('TikTok call failed: '.$e->getMessage().' (code '.$e->getBusinessCode().', request_id '.$e->getRequestId().')');

            return self::FAILURE;
        }

        if (empty($shops)) {
            $this->warn('No authorized shops returned for this token.');

            return self::FAILURE;
        }

        $tokens = array_filter([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'access_token_expire_in' => $this->option('access-expires-at') ? (int) $this->option('access-expires-at') : null,
            'refresh_token_expire_in' => $this->option('refresh-expires-at') ? (int) $this->option('refresh-expires-at') : null,
            'granted_scopes' => $this->option('scopes') ? explode(',', $this->option('scopes')) : [],
        ], fn ($value) => $value !== null && $value !== []);

        $count = $tokenManager->saveTokenSet($tokens, $shops, $this->option('merchant-ref'));

        $this->table(
            ['Shop ID', 'Name', 'Region', 'Cipher'],
            collect($shops)->map(fn ($shop) => [
                $shop['id'],
                $shop['name'] ?? '-',
                $shop['region'] ?? '-',
                substr($shop['cipher'] ?? '', 0, 16).'...',
            ])->all(),
        );

        $this->info("Stored {$count} shop(s). Token records are keyed by shop id.");

        return self::SUCCESS;
    }
}
