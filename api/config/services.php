<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WebxPay Payment Gateway
    |--------------------------------------------------------------------------
    |
    | RSA public-key encryption flow:
    |   plaintext = "{payment_id}|{charge_amount_integer_LKR}"
    |   payment   = base64(openssl_public_encrypt(plaintext, public_key))
    | POST the form_data array to gateway_url.
    |
    */
    'webxpay' => [
        'gateway_url'          => env('WEBXPAY_GATEWAY_URL', 'https://webxpay.com/index.php?route=checkout/billing'),
        'secret_key'           => env('WEBXPAY_SECRET_KEY', '630be963-59e2-447a-8f3b-93b3d7a3bf25'),
        'enc_method'           => env('WEBXPAY_ENC_METHOD', 'JCs3J+6oSz4V0LgE0zi/Bg=='),
        'gateway_id'           => env('WEBXPAY_GATEWAY_ID', ''),
        'handling_fee_enabled' => env('WEBXPAY_HANDLING_FEE_ENABLED', false),
        'handling_fee_rate'    => env('WEBXPAY_HANDLING_FEE_RATE', 3.0),
        // RSA public key — newlines must be literal \n in .env (use double-quoted string)
        'public_key'           => env('WEBXPAY_PUBLIC_KEY',
            "-----BEGIN PUBLIC KEY-----\n" .
            "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC9l2HykxDIDVZeyDPJU4pA0imf\n" .
            "3nWsvyJgb3zTsnN8B0mFX6u5squ5NQcnQ03L8uQ56b4/isHBgiyKwfMr4cpEpCTY\n" .
            "/t1WSdJ5EokCI/F7hCM7aSSSY85S7IYOiC6pKR4WbaOYMvAMKn5gCobEPtosmPLz\n" .
            "gh8Lo3b8UsjPq2W26QIDAQAB\n" .
            "-----END PUBLIC KEY-----"
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Gateway (Dialog/eSMS or similar)
    |--------------------------------------------------------------------------
    */
    'sms' => [
        'provider'   => env('SMS_PROVIDER', 'log'),
        'api_url'    => env('SMS_API_URL', ''),
        'api_key'    => env('SMS_API_KEY', ''),
        'sender_id'  => env('SMS_SENDER_ID', 'GRABBER'),
    ],

];
