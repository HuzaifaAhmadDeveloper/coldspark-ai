<?php

namespace App\Exceptions\Mail;

/**
 * A send failure that will never succeed on retry: invalid recipient,
 * suppressed/complained address, unverified SES sandbox recipient, rejected
 * content. SendCampaignEmailJob catches these and marks the email failed
 * immediately instead of burning retry attempts.
 */
class PermanentMailException extends \RuntimeException
{
}
