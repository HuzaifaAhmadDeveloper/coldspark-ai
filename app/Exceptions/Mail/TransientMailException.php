<?php

namespace App\Exceptions\Mail;

/**
 * A send failure that's likely to succeed on retry: throttling, rate limits,
 * timeouts, connection drops. SendCampaignEmailJob lets these bubble up so
 * the queue's normal retry/backoff handles them.
 */
class TransientMailException extends \RuntimeException
{
}
