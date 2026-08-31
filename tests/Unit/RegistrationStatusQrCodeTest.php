<?php

namespace Tests\Unit;

use App\Services\RegistrationStatusQrCode;
use Tests\TestCase;

class RegistrationStatusQrCodeTest extends TestCase
{
    public function test_it_generates_png_data_uri_for_secure_status_url(): void
    {
        $url = 'https://example.test/pendaftaran/status/01KTESTTOKEN';

        $dataUri = app(RegistrationStatusQrCode::class)
            ->generate($url);

        $this->assertStringStartsWith(
            'data:image/png;base64,',
            $dataUri
        );

        $png = base64_decode(
            substr(
                $dataUri,
                strlen('data:image/png;base64,')
            ),
            true
        );

        $this->assertNotFalse($png);

        $this->assertStringStartsWith(
            "\x89PNG\r\n\x1a\n",
            $png
        );
    }

    public function test_it_returns_null_when_status_url_is_null(): void
    {
        $this->assertNull(
            app(RegistrationStatusQrCode::class)
                ->generate(null)
        );
    }
}