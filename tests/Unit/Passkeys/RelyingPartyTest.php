<?php

namespace Tests\Unit\Passkeys;

use App\Passkeys\RelyingParty;
use PHPUnit\Framework\TestCase;

class RelyingPartyTest extends TestCase
{
    public function test_loopback_address_becomes_localhost(): void
    {
        $this->assertSame('localhost', RelyingParty::id('127.0.0.1'));
        $this->assertSame('localhost', RelyingParty::id('::1'));
    }

    public function test_named_host_stays_unchanged(): void
    {
        $this->assertSame('libreta.test', RelyingParty::id('libreta.test'));
    }

    public function test_bare_app_url_uses_its_hostname(): void
    {
        $this->assertSame(
            'control.kontrolaonline.com',
            RelyingParty::idFromAppUrl('control.kontrolaonline.com'),
        );
    }

    public function test_loopback_origins_also_allow_localhost(): void
    {
        $this->assertSame([
            'http://127.0.0.1:8000',
            'http://localhost:8000',
        ], RelyingParty::origins(['http://127.0.0.1:8000']));
    }
}
