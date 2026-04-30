<?php

declare(strict_types=1);
namespace Pablo1Gustavo\MonologSeq\Exception;

final class SeqDeliveryException extends \RuntimeException
{
    private function __construct(string $message, \Throwable $previous)
    {
        parent::__construct($message, previous: $previous);
    }

    public static function sendFailed(int $retries, \Throwable $previous): self
    {
        $retryName = $retries === 1 ? 'retry' : "retries";
        $retryMessage = $retries > 0 ? " after $retries $retryName" : '';

        return new self("Failed to deliver log to Seq$retryMessage: " . $previous->getMessage(), $previous);
    }
}
