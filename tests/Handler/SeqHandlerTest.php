<?php

declare(strict_types=1);
namespace Pablo1Gustavo\MonologSeq\Test\Handler;

use GuzzleHttp\{Client, HandlerStack, Middleware};
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\{Request, Response};
use Monolog\Level;
use Monolog\Test\MonologTestCase;
use Pablo1Gustavo\MonologSeq\Exception\SeqDeliveryException;
use Pablo1Gustavo\MonologSeq\Formatter\SeqJsonFormatter;
use Pablo1Gustavo\MonologSeq\Handler\SeqHandler;
use PHPUnit\Framework\Attributes\DataProvider;

class SeqHandlerTest extends MonologTestCase
{
    private const URL = 'http://seq:5341/ingest/clef';
    private const API_KEY = 'test-api-key';

    private function createHandler(array &$history = [], Level $level = Level::Debug): SeqHandler
    {
        $mock = new MockHandler(array_fill(0, 10, new Response(201)));
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        return new SeqHandler(self::URL, self::API_KEY, $level, client: new Client(['handler' => $stack]));
    }

    private function failingClient(): Client
    {
        $mock = new MockHandler([new ConnectException('fail', new Request('POST', self::URL))]);

        return new Client(['handler' => HandlerStack::create($mock)]);
    }

    #[DataProvider('invalidUrlProvider')]
    public function test_constructor_throws_on_invalid_url(string $url): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid Seq URL/');
        new SeqHandler($url, self::API_KEY);
    }

    public static function invalidUrlProvider(): array
    {
        return [
            'empty string'    => [''],
            'no scheme'       => ['seq:5341/ingest/clef'],
            'plain text'      => ['not-a-url'],
            'missing host'    => ['http://'],
        ];
    }

    public function test_constructor_throws_on_negative_max_retries(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Max retries/');
        new SeqHandler(self::URL, self::API_KEY, maxRetries: -1);
    }

    public function test_constructor_accepts_zero_max_retries(): void
    {
        $handler = new SeqHandler(self::URL, self::API_KEY, maxRetries: 0);
        $this->assertInstanceOf(SeqHandler::class, $handler);
    }

    public function test_uses_seq_json_formatter_by_default(): void
    {
        $handler = $this->createHandler();
        $this->assertInstanceOf(SeqJsonFormatter::class, $handler->getFormatter());
    }

    public function test_handle_sends_post_to_correct_url(): void
    {
        $history = [];
        $handler = $this->createHandler($history);
        $handler->handle($this->getRecord(Level::Info, 'test'));

        $this->assertCount(1, $history);
        $this->assertSame('POST', $history[0]['request']->getMethod());
        $this->assertSame(self::URL, (string) $history[0]['request']->getUri());
    }

    public function test_handle_sends_clef_content_type_header(): void
    {
        $history = [];
        $handler = $this->createHandler($history);
        $handler->handle($this->getRecord(Level::Info, 'test'));

        $this->assertSame(
            SeqHandler::CLEF_CONTENT_TYPE,
            $history[0]['request']->getHeaderLine('Content-Type')
        );
    }

    public function test_handle_sends_api_key_header(): void
    {
        $history = [];
        $handler = $this->createHandler($history);
        $handler->handle($this->getRecord(Level::Info, 'test'));

        $this->assertSame(
            self::API_KEY,
            $history[0]['request']->getHeaderLine(SeqHandler::SEQ_KEY_HEADER)
        );
    }

    public function test_handle_sends_formatted_record_as_body(): void
    {
        $history = [];
        $handler = $this->createHandler($history);
        $handler->handle($this->getRecord(Level::Info, 'hello world'));

        $body = (string) $history[0]['request']->getBody();
        $this->assertStringContainsString('hello world', $body);
    }

    public function test_handle_ignores_record_below_minimum_level(): void
    {
        $history = [];
        $handler = $this->createHandler($history, Level::Error);
        $handler->handle($this->getRecord(Level::Debug, 'should be ignored'));

        $this->assertCount(0, $history);
    }

    public function test_handle_sends_one_request_per_record(): void
    {
        $history = [];
        $handler = $this->createHandler($history);
        $handler->handle($this->getRecord(Level::Info, 'first'));
        $handler->handle($this->getRecord(Level::Info, 'second'));

        $this->assertCount(2, $history);
    }

    public function test_bubble_true_returns_false(): void
    {
        $handler = $this->createHandler(level: Level::Debug);
        $result = $handler->handle($this->getRecord(Level::Info));

        $this->assertFalse($result);
    }

    public function test_bubble_false_returns_true(): void
    {
        $mock = new MockHandler([new Response(201)]);
        $handler = new SeqHandler(self::URL, self::API_KEY, bubble: false, client: new Client(['handler' => HandlerStack::create($mock)]));
        $result = $handler->handle($this->getRecord(Level::Info));

        $this->assertTrue($result);
    }

    public function test_handle_batch_sends_single_request(): void
    {
        $history = [];
        $handler = $this->createHandler($history);
        $handler->handleBatch([
            $this->getRecord(Level::Info, 'first'),
            $this->getRecord(Level::Warning, 'second'),
            $this->getRecord(Level::Error, 'third'),
        ]);

        $this->assertCount(1, $history);
    }

    public function test_handle_batch_sends_newline_delimited_body(): void
    {
        $history = [];
        $handler = $this->createHandler($history);
        $handler->handleBatch([
            $this->getRecord(Level::Info, 'first'),
            $this->getRecord(Level::Warning, 'second'),
        ]);

        $lines = explode("\n", (string) $history[0]['request']->getBody());
        $this->assertCount(2, $lines);
        $this->assertStringContainsString('first', $lines[0]);
        $this->assertStringContainsString('second', $lines[1]);
    }

    public function test_handle_batch_filters_records_below_minimum_level(): void
    {
        $history = [];
        $handler = $this->createHandler($history, Level::Warning);
        $handler->handleBatch([
            $this->getRecord(Level::Debug, 'ignored'),
            $this->getRecord(Level::Info, 'also ignored'),
            $this->getRecord(Level::Warning, 'included'),
            $this->getRecord(Level::Error, 'also included'),
        ]);

        $body = (string) $history[0]['request']->getBody();
        $this->assertCount(2, explode("\n", $body));
        $this->assertStringNotContainsString('ignored', $body);
        $this->assertStringContainsString('included', $body);
        $this->assertStringContainsString('also included', $body);
    }

    public function test_handle_batch_sends_no_request_when_all_records_filtered(): void
    {
        $history = [];
        $handler = $this->createHandler($history, Level::Error);
        $handler->handleBatch([
            $this->getRecord(Level::Debug),
            $this->getRecord(Level::Info),
        ]);

        $this->assertCount(0, $history);
    }

    public function test_uses_retry_middleware_client_by_default(): void
    {
        $handler = new SeqHandler(self::URL, self::API_KEY);
        $client = (new \ReflectionProperty($handler, 'client'))->getValue($handler);

        $this->assertInstanceOf(\GuzzleHttp\ClientInterface::class, $client);
    }

    public function test_uses_injected_client_when_provided(): void
    {
        $history = [];
        $mock = new MockHandler([new Response(201)]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $handler = new SeqHandler(self::URL, self::API_KEY, client: new Client(['handler' => $stack]), maxRetries: 999);
        $handler->handle($this->getRecord(Level::Info, 'test'));

        $this->assertCount(1, $history);
    }

    public function test_sendPayload_throws_delivery_exception_with_retry_count_in_message(): void
    {
        $handler = new SeqHandler(self::URL, self::API_KEY, client: $this->failingClient(), maxRetries: 3);

        $this->expectException(SeqDeliveryException::class);
        $this->expectExceptionMessageMatches('/after 3 retries/');
        $handler->handle($this->getRecord(Level::Info, 'test'));
    }

    public function test_sendPayload_throws_delivery_exception_without_retry_info_when_zero_retries(): void
    {
        $handler = new SeqHandler(self::URL, self::API_KEY, client: $this->failingClient(), maxRetries: 0);

        $this->expectException(SeqDeliveryException::class);
        $this->expectExceptionMessageMatches('/^Failed to deliver log to Seq:/');
        $handler->handle($this->getRecord(Level::Info, 'test'));
    }

    public function test_sendPayload_wraps_original_exception_as_previous(): void
    {
        $handler = new SeqHandler(self::URL, self::API_KEY, client: $this->failingClient());

        try
        {
            $handler->handle($this->getRecord(Level::Info, 'test'));
            $this->fail('Expected SeqDeliveryException');
        } catch (SeqDeliveryException $e)
        {
            $this->assertInstanceOf(ConnectException::class, $e->getPrevious());
        }
    }

    public function test_handleBatch_throws_delivery_exception(): void
    {
        $handler = new SeqHandler(self::URL, self::API_KEY, client: $this->failingClient());

        $this->expectException(SeqDeliveryException::class);
        $handler->handleBatch([$this->getRecord(Level::Info, 'test')]);
    }

    public function test_handleBatch_skips_records_below_minimum_level(): void
    {
        $history = [];
        $handler = $this->createHandler($history, Level::Error);

        $handler->handleBatch([$this->getRecord(Level::Debug, 'skipped')]);

        $this->assertCount(0, $history);
    }
}
