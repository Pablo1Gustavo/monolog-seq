# Monolog Seq

<p align="center">
    <img
        src="https://upload.wikimedia.org/wikipedia/commons/thumb/3/31/Webysther_20160423_-_Elephpant.svg/3840px-Webysther_20160423_-_Elephpant.svg.png"
        width="200"
    />
    <img
        src="https://datalust.co/assets/seq-logo-light.svg"
        width="250"
    />
</p>

Integrates PHP Monolog with Seq using HTTP ingestion, enabling structured event logging to a centralized Seq server for enhanced log management.

## Installation

```bash
composer require pablo1gustavo/monolog-seq
```

## Usage

This package automatically formats log records as [CLEF](https://clef-json.org/) (Compact Log Event Format) and sends them to Seq via HTTP. The fields `@t`, `@m`/`@mt`, `@l`, and `@x` are set automatically from the log record.

For more details, refer to the [official Seq documentation](https://docs.datalust.co/docs/posting-raw-events).

### Vanilla PHP

You can find a runnable example in [`example.php`](example.php).

```php
use Monolog\Logger;
use Pablo1Gustavo\MonologSeq\Handler\SeqHandler;

$logger = new Logger('app');
$logger->pushHandler(new SeqHandler(
    url: 'http://localhost:5341/ingest/clef',
    apiKey: 'your-api-key',
));

$logger->info("hello my name is {name}", ['name' => 'pablo']);
$logger->warning('warn message', ['abc' => '123', 'def' => [1, 2, 3]]);
$logger->error('something failed', ['exception' => new Exception('error')]);
$logger->debug('debug message', ['date' => new DateTime('2002-01-13')]);
$logger->info('User logged in', ['@i' => 0xABCD1234, 'userId' => 42]);
```

### Laravel

Laravel allows configuring custom Monolog handlers within `config/logging.php`. See [Laravel Logging - Creating Monolog Handler Channels](https://laravel.com/docs/13.x/logging#creating-monolog-handler-channels) for details.

```php
'seq' => [
    'driver'    => 'monolog',
    'level'     => env('LOG_LEVEL', 'debug'),
    'handler'   => \Pablo1Gustavo\MonologSeq\Handler\SeqHandler::class,
    'formatter' => \Pablo1Gustavo\MonologSeq\Formatter\SeqJsonFormatter::class,
    'with' => [
        'url'    => env('SEQ_URL'),
        'apiKey' => env('SEQ_API_KEY'),
    ],
],
```

> The `formatter` key is required. Without it, Laravel applies its default formatter, which produces plain text instead of CLEF.

### Symfony

See [How to Define a Custom Logging Handler](https://symfony.com/doc/current/logging/handlers.html) for details.

Register both the handler and the formatter as services in `config/services.yaml`:

```yaml
services:
    Pablo1Gustavo\MonologSeq\Formatter\SeqJsonFormatter: ~

    Pablo1Gustavo\MonologSeq\Handler\SeqHandler:
        arguments:
            $url: '%env(SEQ_URL)%'
            $apiKey: '%env(SEQ_API_KEY)%'
```

Then reference them in `config/packages/monolog.yaml`:

```yaml
monolog:
    handlers:
        seq:
            type:      service
            id:        Pablo1Gustavo\MonologSeq\Handler\SeqHandler
            formatter: Pablo1Gustavo\MonologSeq\Formatter\SeqJsonFormatter
```

> The `formatter` option is required. Without it, Symfony's MonologBundle applies `LineFormatter` by default, which produces plain text instead of CLEF.

## Batch Ingestion

By default, each log record is sent as a separate HTTP request. For better performance, wrap `SeqHandler` with Monolog's `BufferHandler` to accumulate records and flush them in a single request at the end of the process:

```php
use Monolog\Handler\BufferHandler;
use Pablo1Gustavo\MonologSeq\Handler\SeqHandler;

$logger->pushHandler(
    new BufferHandler(
        new SeqHandler(
            url: 'http://localhost:5341/ingest/clef',
            apiKey: 'your-api-key',
        )
    )
);
```

The `BufferHandler` automatically flushes at the end of the request via `register_shutdown_function()`.

## CLEF Fields via Context

Any [CLEF property](https://clef-json.org/) passed in the log context is promoted to a top-level field in the event. This includes tracing, event identity, and custom overrides:

```php
// Event ID — used by Seq to group and deduplicate events
$logger->info('User logged in', [
    '@i' => 0xABCD1234,
    'userId' => 42,
]);
// Distributed tracing (OpenTelemetry)
$logger->info('Incoming request', [
    '@tr' => $traceId,
    '@sp' => $spanId,
]);
// Override formatter defaults
$logger->info('Custom timestamp', [
    '@t' => '2020-01-01T00:00:00Z',
]);
```

Supported CLEF fields: `@i` (event ID), `@tr` (trace ID), `@sp` (span ID), `@ps` (parent span ID), `@st` (span start), `@sk` (span kind), `@sc` (instrumentation scope), `@ra` (resource attributes).
