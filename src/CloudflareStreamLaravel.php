<?php

namespace AFloeter\CloudflareStream;

class CloudflareStreamLaravel extends CloudflareStream
{
    public function __construct()
    {
        parent::__construct(
            config('cloudflare-stream.accountId'),
            config('cloudflare-stream.authKey'),
            config('cloudflare-stream.authEMail'),
            config('cloudflare-stream.privateKeyId'),
            config('cloudflare-stream.privateKeyToken')
        );
    }
}
