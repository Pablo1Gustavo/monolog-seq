<?php

declare(strict_types=1);
namespace Pablo1Gustavo\MonologSeq\Http;

use GuzzleHttp\{Client, ClientInterface, HandlerStack, Middleware};
use GuzzleHttp\Exception\ConnectException;
use Psr\Http\Message\{RequestInterface, ResponseInterface};

final class RetryMiddleware
{
    public const DEFAULT_DELAY = 0;

    private function __construct()
    {
    }

    public static function build(int $retries = 3): callable
    {
        return Middleware::retry(
            static function (
                int $retryCount,
                RequestInterface $_request,
                ?ResponseInterface $response,
                ?\Throwable $exception,
            ) use ($retries): bool
            {
                if ($retryCount >= $retries)
                {
                    return false;
                }
                if ($exception instanceof ConnectException)
                {
                    return true;
                }

                return $response?->getStatusCode() === 503;
            },
            static fn (): int => self::DEFAULT_DELAY,
        );
    }

    public static function buildClient(int $retries = 3): ClientInterface
    {
        $stack = HandlerStack::create();
        $stack->push(self::build($retries));

        return new Client(['handler' => $stack]);
    }
}
