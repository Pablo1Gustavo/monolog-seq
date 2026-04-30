<?php

declare(strict_types=1);
namespace Pablo1Gustavo\MonologSeq\Test\Http;

use GuzzleHttp\{Client, ClientInterface, HandlerStack};
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\{Request, Response};
use Pablo1Gustavo\MonologSeq\Http\RetryMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RetryMiddlewareTest extends TestCase
{
    private function makeClient(MockHandler $mock, int $retries = 3): Client
    {
        $stack = HandlerStack::create($mock);
        $stack->push(RetryMiddleware::build(retries: $retries));

        return new Client(['handler' => $stack]);
    }

    private function connectException(): ConnectException
    {
        return new ConnectException('Connection refused', new Request('POST', 'http://example.com'));
    }

    public function test_build_returns_callable(): void
    {
        $this->assertIsCallable(RetryMiddleware::build());
    }

    public function test_buildClient_returns_client_interface(): void
    {
        $this->assertInstanceOf(ClientInterface::class, RetryMiddleware::buildClient());
    }

    #[DataProvider('nonRetriableStatusCodeProvider')]
    public function test_does_not_retry_on_non_retriable_status(int $status): void
    {
        $mock = new MockHandler([new Response($status), new Response(201)]);
        $client = $this->makeClient($mock, retries: 3);

        try
        {
            $client->request('POST', 'http://example.com');
        } catch (\Throwable)
        {
        }

        $this->assertSame(1, count($mock));
    }

    public static function nonRetriableStatusCodeProvider(): array
    {
        return [
            'HTTP 400' => [400],
            'HTTP 401' => [401],
            'HTTP 500' => [500],
        ];
    }

    public function test_retries_on_503_up_to_configured_limit(): void
    {
        $mock = new MockHandler([new Response(503), new Response(503), new Response(201)]);
        $client = $this->makeClient($mock, retries: 2);

        $response = $client->request('POST', 'http://example.com');

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(0, count($mock));
    }

    public function test_retries_on_connect_exception_up_to_configured_limit(): void
    {
        $mock = new MockHandler([$this->connectException(), $this->connectException(), new Response(201)]);
        $client = $this->makeClient($mock, retries: 2);

        $response = $client->request('POST', 'http://example.com');

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(0, count($mock));
    }

    public function test_does_not_retry_beyond_configured_limit(): void
    {
        $mock = new MockHandler([
            new Response(503),
            new Response(503),
            new Response(503),
            new Response(201),
        ]);
        $client = $this->makeClient($mock, retries: 2);

        try
        {
            $client->request('POST', 'http://example.com');
        } catch (\Throwable)
        {
        }

        $this->assertSame(1, count($mock)); // 3 consumed, 1 remaining
    }

    public function test_succeeds_without_retry_when_response_is_200(): void
    {
        $mock = new MockHandler([new Response(200), new Response(201)]);
        $client = $this->makeClient($mock, retries: 3);

        $response = $client->request('POST', 'http://example.com');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, count($mock));
    }

    public function test_zero_retries_returns_successful_response(): void
    {
        $mock = new MockHandler([new Response(200)]);
        $client = $this->makeClient($mock, retries: 0);

        $response = $client->request('POST', 'http://example.com');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(0, count($mock));
    }

    public function test_zero_retries_does_not_retry_on_failure(): void
    {
        $mock = new MockHandler([new Response(503), new Response(201)]);
        $client = $this->makeClient($mock, retries: 0);

        try
        {
            $client->request('POST', 'http://example.com');
        } catch (\Throwable)
        {
        }

        $this->assertSame(1, count($mock)); // only 503 consumed, no retry
    }
}
