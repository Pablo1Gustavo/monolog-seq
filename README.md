# Monolog Seq

<p align="center">
    <img
        src="https://upload.wikimedia.org/wikipedia/commons/thumb/3/31/Webysther_20160423_-_Elephpant.svg/2560px-Webysther_20160423_-_Elephpant.svg.png"
        width="200"
    />
    <img
        src="https://blog.getseq.net/content/images/2018/09/Seq-Main-Light-400px.png"
        width="200"
        style="margin-left: 20px;"
    />
</p>

Integrates PHP Monolog with Seq using HTTP ingestion, enabling structured event logging to a centralized Seq server for enhanced log management.

## Installation

Install the latest version with

```bash
composer require pablo1gustavo/monolog-seq
```

## Usage

This package **automatically sets** the [CLEF](https://clef-json.org/) request keys, including **@t**, **@m**, **@mt**, **@l**, and **@x**, ensuring seamless structured logging.

For more detailed usage instructions, refer to the [official Seq documentation](https://docs.datalust.co/docs/posting-raw-events).

### Usage (Vanilla PHP)

You can find an example in [`example.php`](example.php).

```php
$seqUrl = "http://localhost:5341/api/events/raw";
$seqApiKey = "H16XK1wLgC0LDsen5fwA";

$logger = new Logger('seq');
$logger->pushHandler(new SeqHandler($seqUrl, $seqApiKey));

$logger->critical('error', ['excepasdtion' => new Exception('error')]);
$logger->warning('warn message', ['abc' => "123", 'def' => [1,2,3]]); 
$logger->info("hello my name is {name}", ['name' => 'pablo']);
$logger->debug("debug message", ['date' => new DateTime("2002-01-13")]);
$logger->emergency("teste");
```

### Usage (Laravel)

Laravel allows you to configure **custom Monolog handlers**, such as this package, within its logging configuration file.

For step-by-step instructions, refer to the [Laravel Logging - Creating Monolog Handler Channels](https://laravel.com/docs/12.x/logging#creating-monolog-handler-channels) documentation.

Example:
```php
        'seq' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => \Pablo1Gustavo\MonologSeq\Handler\SeqHandler::class,
            'formatter' => \Pablo1Gustavo\MonologSeq\Formatter\SeqJsonFormatter::class,
            'with' => [
                'url' => env('SEQ_URL'),
                'apiKey' => env('SEQ_API_KEY'),
            ],
        ]
```