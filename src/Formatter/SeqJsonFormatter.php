<?php
declare(strict_types=1);
namespace Pablo1Gustavo\MonologSeq\Formatter;

use Monolog\Formatter\JsonFormatter;
use Monolog\Level;
use Monolog\LogRecord;
use Pablo1Gustavo\MonologSeq\Enum\CLEFProperty as CLEF;

class SeqJsonFormatter extends JsonFormatter
{
    const VALID_VAR_REGEX = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';

    public static function getLogLevel(Level $level): string
    {
        return match ($level) {
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
    {;
        $normalized = $this->normalize(
            $this->normalizeRecordForCLEFProperties($record)
        );
        return $this->toJson($normalized, true) . ($this->appendNewline ? "\n" : '');
    }

    public static function messageHasTemplate(string $message, string $key): bool
    {
        return preg_match(self::VALID_VAR_REGEX, $key) && strpos($message, "{{$key}}") !== false;
    }

    protected function normalizeRecordForCLEFProperties(LogRecord $record): array
    {
        $payload = [...$record->context, ...$record->extra]; 
        $messageProperty = CLEF::MESSAGE;

        foreach ($record->context as $key => $_value)
        {
            if (self::messageHasTemplate($record->message, $key))
            {
                $messageProperty = CLEF::MESSAGE_TEMPLATE;
            }
            if (strtoupper($key) == 'exception' || $key == CLEF::EXCEPTION->value)
            {
                unset($payload[$key]);
                $exception = $this->normalizeExceptionAsText($record->context['exception']);
                $payload[CLEF::EXCEPTION->value] = $exception;
            }
        }

        return array_merge($payload, [
            CLEF::TIMESTAMP->value  => $record->datetime->format('c'),
            CLEF::LEVEL->value      => self::getLogLevel($record->level),
            $messageProperty->value => $record->message,
        ]);
    }

    protected function normalizeExceptionAsText(mixed $exception): string
    {
        if ($exception instanceof \Throwable)
        {
            return sprintf(
                "%s: %s in %s:%d\nStack trace:\n%s",
                get_class($exception),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $exception->getTraceAsString()
            );
        }
        return is_string($exception) ? $exception : json_encode($exception);
    }
}