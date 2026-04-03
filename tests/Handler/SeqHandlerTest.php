<?php

declare(strict_types=1);
namespace Pablo1Gustavo\MonologSeq\Test\Handler;

use GuzzleHttp\{Client, HandlerStack, Middleware};
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Monolog\Level;
use Monolog\Test\MonologTestCase;
use Pablo1Gustavo\MonologSeq\Formatter\SeqJsonFormatter;
use Pablo1Gustavo\MonologSeq\Handler\SeqHandler;

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
}
