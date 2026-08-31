<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class RegistrationStatusQrCode
{
    public function generate(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $qrCode = QrCode::create($url)
            ->setSize(220)
            ->setMargin(10);

        $result = (new PngWriter())->write($qrCode);

        return 'data:image/png;base64,'
            .base64_encode($result->getString());
    }
}