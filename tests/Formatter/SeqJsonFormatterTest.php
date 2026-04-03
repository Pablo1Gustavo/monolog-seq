<?php

declare(strict_types=1);
namespace Pablo1Gustavo\MonologSeq\Test\Formatter;

use Exception;
use Monolog\Level;
use Monolog\Test\MonologTestCase;
use Pablo1Gustavo\MonologSeq\Enum\CLEFProperty as CLEF;
use Pablo1Gustavo\MonologSeq\Formatter\SeqJsonFormatter;

class SeqJsonFormatterTest extends MonologTestCase
{
    protected $formatter;

    public function setUp(): void
    {
        $this->formatter = new SeqJsonFormatter();
    }

    public function test_format_log_levels()
    {
        $testData = [
            [Level::Debug, 'Debug'],
            [Level::Info, 'Information'],
            [Level::Notice, 'Information'],
            [Level::Warning, 'Warning'],
            [Level::Error, 'Error'],
            [Level::Critical, 'Fatal'],
            [Level::Alert, 'Fatal'],
            [Level::Emergency, 'Fatal'],
        ];
        foreach ($testData as [$level, $expectedStrLevel])
        {
            $this->assertStringContainsString(
                "\"@l\":\"$expectedStrLevel\"",
                $this->formatter->format($this->getRecord($level))
            );
        }
    }

    public function test_format_basic_message()
    {
        $datetime = new \DateTimeImmutable("2021-01-01 04:20:59");

        $record = $this->getRecord(
            level: Level::Debug,
            message: "teste",
            context: ['key' => 'value'],
            extra: ['key2' => 'value2'],
            datetime: $datetime,
        );

        $formatted = $this->formatter->format($record);
        $expected = json_encode([
            '@t'   => "2021-01-01T04:20:59+00:00",
            '@l'   => 'Debug',
            '@m'   => 'teste',
            'key'  => 'value',
            'key2' => 'value2',
        ]);
        $this->assertSame($expected, $formatted);
    }

    public function test_format_message_template()
    {
        $record = $this->getRecord(
            message: "hello my name is {name} and I'm {age} years old",
            context: ['name' => 'pablo'],
            extra: ['age' => 25],
        );
        $formatted = $this->formatter->format($record);

        $this->assertStringNotContainsString("\"@m\"", $formatted);
        $this->assertStringContainsString("\"@mt\":\"hello my name is {name} and I'm {age} years old\"", $formatted);
    }

    public function test_format_message_with_exception_without_trace()
    {
        $this->formatter = new SeqJsonFormatter(includeStacktraces: false);

        try
        {
            throw new Exception("teste", 123);
        } catch (Exception $e)
        {
            $record = $this->getRecord(
                level: Level::Error,
                message: "error",
                context: ['exception' => $e]
            );
            $formatted = $this->formatter->format($record);

            $this->assertStringContainsString("\"class\":\"Exception\"", $formatted);
            $this->assertStringContainsString("\"message\":\"teste\"", $formatted);
            $this->assertStringContainsString("\"code\":123", $formatted);
            $this->assertStringContainsString("\"file\":", $formatted);
            $this->assertStringContainsString("\"@x\"", $formatted);
            $this->assertStringContainsString("Exception class: Exception", $formatted);
            $this->assertStringContainsString("Exception message: teste", $formatted);
            $this->assertStringContainsString("Exception code: 123", $formatted);
            $this->assertStringContainsString("Exception file: ", $formatted);

            $this->assertStringNotContainsString("\"trace\":", $formatted);
            $this->assertStringNotContainsString("Exception trace:", $formatted);

        }
    }

    public function test_format_message_with_exception_with_trace()
    {
        $this->formatter = new SeqJsonFormatter(includeStacktraces: true);

        try
        {
            throw new Exception("teste", 123);
        } catch (Exception $e)
        {
            $record = $this->getRecord(
                level: Level::Error,
                message: "error",
                context: ['exception' => $e]
            );
            $formatted = $this->formatter->format($record);

            $this->assertStringContainsString("\"trace\":", $formatted);
            $this->assertStringContainsString("Exception trace:", $formatted);
        }
    }

    public function test_format_allows_overriding_clef_properties_from_context(): void
    {
        $record = $this->getRecord(
            level: Level::Info,
            message: 'test',
            context: [
                '@t' => '1970-01-01T00:00:00+00:00',
                '@l' => 'Fatal',
                '@m' => 'overridden message',
            ],
        );

        $payload = json_decode($this->formatter->format($record), true);

        $this->assertSame('1970-01-01T00:00:00+00:00', $payload['@t']);
        $this->assertSame('Fatal', $payload['@l']);
        $this->assertSame('overridden message', $payload['@m']);
    }

    public function test_format_preserves_event_id_from_context(): void
    {
        $record = $this->getRecord(
            level: Level::Info,
            message: 'user logged in',
            context: [CLEF::EVENT_ID->value => 0xABCD],
        );

        $payload = json_decode($this->formatter->format($record), true);

        $this->assertSame(0xABCD, $payload[CLEF::EVENT_ID->value]);
    }

    public function test_format_preserves_trace_and_span_ids_from_context(): void
    {
        $record = $this->getRecord(
            level: Level::Info,
            message: 'traced request',
            context: [
                CLEF::TRACE_ID->value => 'abc123',
                CLEF::SPAN_ID->value  => 'def456',
            ],
        );

        $payload = json_decode($this->formatter->format($record), true);

        $this->assertSame('abc123', $payload[CLEF::TRACE_ID->value]);
        $this->assertSame('def456', $payload[CLEF::SPAN_ID->value]);
    }
}
