<?php

namespace App\Console\Commands;

use App\Services\TikTok\Resources\AuthService;
use App\Services\TikTok\TokenManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// #[Signature('app:my-testing-command')]
// #[Description('Command description')]
class GetShopDetailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:shop-detail {shop_id? : The ID of the shop to inspect}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get specific shop detail using shop ID';

    /**
     * Execute the console command.
     */
    public function handle(TokenManager $tokenManager)
    {
        $shopId = $this->argument('shop_id') ?? '';
        $this->info("Fetching data for Shop ID: {$shopId}...");
        $shopData = $tokenManager->shop($shopId);

        if ($shopData === null) {
            $this->warn("No cache record found for Shop ID: {$shopId}");
            return Command::FAILURE;
        }

        // 3. Display the result nicely in the terminal
        $this->info('Shop record found:');
        dump($shopData);

        return Command::SUCCESS;
    }
}
