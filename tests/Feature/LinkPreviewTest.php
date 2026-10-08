<?php

namespace Tests\Feature;

use Tests\TestCase;

class LinkPreviewTest extends TestCase
{
    public function test_whatsapp_receives_the_link_preview(): void
    {
        $this->get('/', [
            'User-Agent' => 'WhatsApp/2.23.20.0',
        ])
            ->assertOk()
            ->assertSee('property="og:title" content="'.e(config('libreta.company')).'"', false)
            ->assertSee('Ingresos, egresos y el cambio del día. Tu libreta, siempre a mano.', false)
            ->assertSee(asset('og.png'), false)
            ->assertDontSee('Cargando', false);
    }

    public function test_whatsapp_does_not_receive_html_for_the_preview_image(): void
    {
        $size = getimagesize(public_path('og.png'));

        $this->assertIsArray($size);
        $this->assertSame(1200, $size[0]);
        $this->assertSame(630, $size[1]);

        $this->get('/og.png', [
            'User-Agent' => 'WhatsApp/2.23.20.0',
        ])
            ->assertNotFound()
            ->assertDontSee('og:title', false);
    }

    public function test_login_page_exposes_the_link_preview(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('property="og:image"', false)
            ->assertSee(asset('og.png'), false)
            ->assertSee('Ingresos, egresos y el cambio del día. Tu libreta, siempre a mano.', false);
    }
}
