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
class GetShopsListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:shops-list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the entire authorized shops list';

    /**
     * Execute the console command.
     */
    public function handle(TokenManager $tokenManager)
    {
        $allShops = $tokenManager->allShops();
        dump($allShops);

        return Command::SUCCESS;
    }
}
