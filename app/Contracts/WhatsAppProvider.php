<?php

namespace App\Contracts;

interface WhatsAppProvider
{
    public function sendTemplate(
        string $recipient,
        string $template,
        string $languageCode = 'id',
        array $parameters = []
    ): array;
}