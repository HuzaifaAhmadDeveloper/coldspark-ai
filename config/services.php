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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // EmailSendingService (app/Services/EmailSendingService.php): which Laravel
    // mailer (config/mail.php mailers.*) campaign sends try first, and which
    // one to fall back to on failure. Both are just mailer names — adding
    // SendGrid/Mailgun/Postmark later means adding a mailer + pointing one of
    // these at it, not changing any campaign code.
    'email' => [
        'primary' => env('MAIL_PRIMARY_PROVIDER', 'ses'),
        'fallback' => env('MAIL_FALLBACK_PROVIDER', 'smtp'),

        // Provider-aware throttling — SendCampaignEmailJob rate-limits itself
        // to this many sends/second (default matches SES sandbox: 1/sec), and
        // campaigns:dispatch-due refuses to queue more than this many sends
        // in a rolling 24h window (default matches SES sandbox: 200/day).
        // Raise both once SES production access lifts these caps — nothing
        // else in the code needs to change.
        'rate_per_second' => (int) env('EMAIL_SEND_RATE_PER_SECOND', 1),
        'daily_limit' => (int) env('EMAIL_DAILY_LIMIT', 200),
        'batch_size' => (int) env('EMAIL_BATCH_SIZE', 200),
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    // Campaign module: secures the inbound bounce/reply webhook and, for
    // inbound replies, the reply+{token}@{domain} address CampaignEmailMailable
    // sets as Reply-To. Both are optional — leave blank to disable reply
    // detection via webhook and rely on the manual "Mark Replied" action.
    'email_webhook' => [
        'secret' => env('EMAIL_WEBHOOK_SECRET'),
        'reply_domain' => env('MAIL_REPLY_DOMAIN'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'groq' => [
    'key'   => env('GROQ_API_KEY'),
    'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
],

];
