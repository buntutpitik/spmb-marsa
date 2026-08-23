<?php

namespace Tests\Feature;

use App\Contracts\WhatsAppProvider;
use App\Services\MetaWhatsappProvider;
use App\Services\WhatsApp\FakeWhatsAppProvider;
use Tests\TestCase;

class WhatsAppServiceProviderTest extends TestCase
{
    public function test_whatsapp_provider_resolves_to_fake_provider(): void
    {
        config()->set('whatsapp.provider', 'fake');

        $provider = app(WhatsAppProvider::class);

        $this->assertInstanceOf(
            FakeWhatsAppProvider::class,
            $provider
        );
    }

    public function test_resolved_fake_provider_implements_contract(): void
    {
        config()->set('whatsapp.provider', 'fake');

        $provider = app(WhatsAppProvider::class);

        $this->assertInstanceOf(
            WhatsAppProvider::class,
            $provider
        );
    }

    public function test_whatsapp_provider_can_resolve_to_meta_provider(): void
    {
        config()->set('whatsapp.provider', 'meta');

        $provider = app(WhatsAppProvider::class);

        $this->assertInstanceOf(
            MetaWhatsappProvider::class,
            $provider
        );

        $this->assertInstanceOf(
            WhatsAppProvider::class,
            $provider
        );
    }
}