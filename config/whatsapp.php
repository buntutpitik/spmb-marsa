<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Provider
    |--------------------------------------------------------------------------
    */

    'provider' => env('WHATSAPP_PROVIDER', 'fake'),

    'providers' => [
        'fake' => [
            'driver' => App\Services\WhatsApp\FakeWhatsAppProvider::class,
        ],

        'meta' => [
            'driver' => App\Services\MetaWhatsappProvider::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Templates
    |--------------------------------------------------------------------------
    */

    'templates' => [

        'registration_success' => [
            'name' => env(
                'META_WA_TEMPLATE_REGISTRATION_SUCCESS',
                'registration_success'
            ),
            'language' => env(
                'META_WA_TEMPLATE_REGISTRATION_SUCCESS_LANG',
                'id'
            ),
        ],

        'registration_accepted' => [
            'name' => env(
                'META_WA_TEMPLATE_REGISTRATION_ACCEPTED',
                'registration_accepted'
            ),
            'language' => env(
                'META_WA_TEMPLATE_REGISTRATION_ACCEPTED_LANG',
                'id'
            ),
        ],

        'registration_rejected' => [
            'name' => env(
                'META_WA_TEMPLATE_REGISTRATION_REJECTED',
                'registration_rejected'
            ),
            'language' => env(
                'META_WA_TEMPLATE_REGISTRATION_REJECTED_LANG',
                'id'
            ),
        ],

        'reenrollment_complete' => [
            'name' => env(
                'META_WA_TEMPLATE_REENROLLMENT_COMPLETE',
                'reenrollment_complete'
            ),
            'language' => env(
                'META_WA_TEMPLATE_REENROLLMENT_COMPLETE_LANG',
                'id'
            ),
        ],

        'registration_withdrawn' => [
            'name' => env(
                'META_WA_TEMPLATE_REGISTRATION_WITHDRAWN',
                'registration_withdrawn'
            ),
            'language' => env(
                'META_WA_TEMPLATE_REGISTRATION_WITHDRAWN_LANG',
                'id'
            ),
        ],

    ],

];
