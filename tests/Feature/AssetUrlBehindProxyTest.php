<?php

namespace Tests\Feature;

use Tests\TestCase;

class AssetUrlBehindProxyTest extends TestCase
{
    public function test_asset_urls_use_the_forwarded_https_origin(): void
    {
        $response = $this->get('/', [
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'fancy-telegram-xxxxx-8000.app.github.dev',
            'X-Forwarded-Port' => '443',
        ]);

        $response->assertOk();
        $response->assertSee('https://fancy-telegram-xxxxx-8000.app.github.dev/build/', false);
        $response->assertDontSee('http://localhost/build/', false);
    }
}
