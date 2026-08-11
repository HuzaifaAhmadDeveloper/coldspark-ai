<?php

namespace App\Services\Mail;

/**
 * Outcome of a successful EmailSendingService::send() call — which provider
 * actually delivered it (ses/smtp/...) and the transport-assigned message id.
 */
final class SendResult
{
    public function __construct(
        public readonly string $provider,
        public readonly ?string $messageId,
    ) {}
}
