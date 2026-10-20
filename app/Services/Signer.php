<?php

namespace App\Services;

class Signer
{
    public function __construct(protected string $appSecret)
    {
    }

    /**
     * Generate the TikTok Shop request signature.
     *
     * Algorithm (per TikTok Shop Partner docs):
     * 1. Take all query params EXCEPT `sign` and `access_token`, sort by key (ASCII).
     * 2. Concatenate as {key}{value}{key}{value}...
     * 3. Prepend the API path: {path}{concatenated}
     * 4. If the request has a JSON body, append the raw body string.
     * 5. Wrap with the app secret: {secret}{input}{secret}
     * 6. HMAC-SHA256 with the app secret, hex-encoded.
     * May refer to https://partner.tiktokshop.com/docv2/page/sign-your-api-request
     */
    public function sign(string $path, array $query, ?string $body = null): string
    {
        unset($query['sign'], $query['access_token']);

        foreach ($query as $value) {
            if (is_array($value)) {
                throw new \InvalidArgumentException(
                    'Query param values must be strings. Normalize arrays (e.g. comma-separated) via TikTokHttpClient before signing.',
                );
            }
        }

        ksort($query);
        $input = $path;

        foreach ($query as $key => $value) {
            $input .= $key.$value;
        }

        if ($body !== null && $body !== '') {
            $input .= $body;
        }

        $input = $this->appSecret.$input.$this->appSecret;
        return hash_hmac('sha256', $input, $this->appSecret);
    }
}
