<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaTest extends TestCase
{
    public function test_manifest_installs_as_a_standalone_app(): void
    {
        $response = $this->get(route('pwa.manifest'));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/manifest+json',
            (string) $response->headers->get('content-type')
        );

        $manifest = $response->json();

        $this->assertSame('Libreta', $manifest['name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/', $manifest['start_url']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertCount(3, $manifest['icons']);
    }

    public function test_login_is_wired_as_an_installable_app(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('apple-mobile-web-app-capable', false)
            ->assertSee('apple-touch-icon', false)
            ->assertSee('id="lb-splash"', false)
            ->assertSee('id="lb-page-loader"', false)
            ->assertSee('Cargando', false)
            ->assertSee('window.__lbSplashShown', false)
            ->assertSee('viewport-fit=cover', false);

        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));
        $this->assertFileExists(public_path('icons/icon-512.png'));
        $this->assertFileExists(public_path('icons/apple-touch-icon.png'));
    }

    public function test_app_bundle_registers_the_service_worker(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('build/manifest.json')), true, 512, JSON_THROW_ON_ERROR);
        $script = (string) file_get_contents(public_path('build/'.$manifest['resources/js/app.js']['file']));

        $this->assertStringContainsString('serviceWorker.register', $script);
        $this->assertStringContainsString('/sw.js', $script);
    }
}
