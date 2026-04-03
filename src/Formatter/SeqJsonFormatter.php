<?php

declare(strict_types=1);
namespace Pablo1Gustavo\MonologSeq\Formatter;

use Monolog\Formatter\JsonFormatter;
use Monolog\{Level, LogRecord};
use Pablo1Gustavo\MonologSeq\Enum\CLEFProperty as CLEF;

class SeqJsonFormatter extends JsonFormatter
{
    private const VALID_VAR_REGEX = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';

    public function __construct(bool $includeStacktraces = true)
    {
        parent::__construct(
            appendNewline: false,
            includeStacktraces: $includeStacktraces
        );
    }

    public static function getLogLevel(Level $level): string
    {
        return match ($level)
        {
            Level::Debug     => 'Debug',
            Level::Info      => 'Information',
            Level::Notice    => 'Information',
            Level::Warning   => 'Warning',
            Level::Error     => 'Error',
            Level::Critical  => 'Fatal',
            Level::Alert     => 'Fatal',
            Level::Emergency => 'Fatal',
        };
    }

    public function format(LogRecord $record): string
    {
        $normalized = $this->normalize(
            $this->normalizeRecordForSeq($record)
        );
        return $this->toJson($normalized, true);
    }

    public static function messageHasTemplate(string $message, string $key): bool
    {
        return preg_match(self::VALID_VAR_REGEX, $key) && strpos($message, "{{$key}}") !== false;
    }

    protected function normalizeRecordForSeq(LogRecord $record): array
    {
        $payload = [...$record->context, ...$record->extra];

        $messageProperty = CLEF::MESSAGE;
        $hasException = isset($payload[CLEF::EXCEPTION->value]);

        foreach ($payload as $key => $value)
        {
            if ($messageProperty === CLEF::MESSAGE && self::messageHasTemplate($record->message, $key))
            {
                $messageProperty = CLEF::MESSAGE_TEMPLATE;
            }
            if (!$hasException && $value instanceof \Throwable)
            {
                $payload[CLEF::EXCEPTION->value] = $this->normalizeExceptionAsText($value);
                $hasException = true;
            }
            if ($messageProperty === CLEF::MESSAGE_TEMPLATE && $hasException)
            {
                break;
            }
        }

        return array_merge([
            CLEF::TIMESTAMP->value  => $record->datetime->format('c'),
            CLEF::LEVEL->value      => self::getLogLevel($record->level),
            $messageProperty->value => $record->message,
        ], $payload);
    }

    protected function normalizeExceptionAsText(\Throwable $exception): string
    {
        $exceptionText = sprintf(
            "Exception class: %s\nException message: %s\nException code: %d\nException file: %s:%d",
            get_class($exception),
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getFile(),
            $exception->getLine(),
        );
        if ($this->includeStacktraces)
        {
            $exceptionText .= "\nException trace:\n" . $exception->getTraceAsString();
        }
        return $exceptionText;
    }
}
