<?php

namespace Database\Seeders;

use App\Enums\ApiClientSecretEnum;
use App\Models\ApiClient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApiClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            [
                'name' => 'Chicha tenant',
                'identifier' => 'chicha_sgdp_1',
                'allowed_ips' => ['127.0.0.1'],
                'status' => ApiClient::STATUS_ACTIVE,
                'callback_url' => 'https://chicha-demo.example.com',
                'secret_data' => [
                    [
                        'key_id' => 'inbound-secret-1',
                        'direction' => ApiClientSecretEnum::INBOUND->value,
                        'status' => ApiClientSecretEnum::ACTIVE->value,
                    ],
                    [
                        'key_id' => 'callback-secret-1',
                        'direction' => ApiClientSecretEnum::CALLBACK->value,
                        'status' => ApiClientSecretEnum::ACTIVE->value,
                    ],
                ],
            ],
            [
                'name' => 'FishnCo tenant',
                'identifier' => 'fishnco_sgdp_1',
                'allowed_ips' => ['127.0.0.1'],
                'status' => ApiClient::STATUS_ACTIVE,
                'callback_url' => 'https://fishnco-demo.example.com',
                'secret_data' => [
                    [
                        'key_id' => 'inbound-secret-1',
                        'direction' => ApiClientSecretEnum::INBOUND->value,
                        'status' => ApiClientSecretEnum::ACTIVE->value,
                    ],
                    [
                        'key_id' => 'callback-secret-1',
                        'direction' => ApiClientSecretEnum::CALLBACK->value,
                        'status' => ApiClientSecretEnum::ACTIVE->value,
                    ],
                ],
            ],
        ];

        DB::transaction(function () use ($clients) {
            foreach ($clients as $clientData) {
                // Find by stable identifier, including inactive clients.
                $client = ApiClient::firstOrCreate(
                    ['identifier' => $clientData['identifier']],
                    [
                        'name' => $clientData['name'],
                        'allowed_ips' => $clientData['allowed_ips'],
                        'status' => $clientData['status'],
                        'callback_url' => $clientData['callback_url'],
                        'client_id' => 'sgdplc_' . Str::lower(
                            Str::random(32)
                        ),
                    ]
                );

                // Update ordinary client properties without changing client_id.
                $client->update([
                    'name' => $clientData['name'],
                    'allowed_ips' => $clientData['allowed_ips'],
                    'status' => $clientData['status'],
                    'callback_url' => $clientData['callback_url'],
                ]);

                foreach ($clientData['secret_data'] as $secretData) {
                    $secret = $client->secrets()->firstOrCreate(
                        ['key_id' => $secretData['key_id']],
                        [
                            'secret_encrypted' => Crypt::encryptString(
                                $plainSecret = Str::random(64)
                            ),
                            'direction' => $secretData['direction'],
                            'status' => $secretData['status'],
                        ]
                    );

                    if ($secret->wasRecentlyCreated) {
                        $this->command->newLine();

                        $this->command->info(
                            'New API secret generated!'
                        );

                        $this->command->line(
                            'Client: ' . $client->name
                        );

                        $this->command->line(
                            'Client ID: ' . $client->client_id
                        );

                        $this->command->line(
                            'Key ID: ' . $secret->key_id
                        );

                        $this->command->line(
                            'Direction: ' . $secret->direction
                        );

                        $this->command->line(
                            'Plaintext Secret: ' . $plainSecret
                        );

                        $this->command->warn(
                            'Copy this secret now. It will not be displayed again.'
                        );

                        $this->command->newLine();
                    }

                    $secret->update([
                        'direction' => $secretData['direction'],
                        'status' => $secretData['status'],
                    ]);
                }
            }
        });
    }
}
