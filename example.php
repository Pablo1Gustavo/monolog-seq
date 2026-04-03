<?php
use Monolog\Logger;
use Pablo1Gustavo\MonologSeq\Handler\SeqHandler;

require_once __DIR__ . '/vendor/autoload.php';

$logger = new Logger('app');
$logger->pushHandler(
    new SeqHandler(
        url: 'http://localhost:5341/ingest/clef',
        apiKey: 'your-api-key',
    )
);

$logger->info("hello my name is {name}", ['name' => 'pablo']);
$logger->warning('warn message', ['abc' => '123', 'def' => [1, 2, 3]]);
$logger->error('something failed', ['exception' => new Exception('error')]);
$logger->debug('debug message', ['date' => new DateTime('2002-01-13')]);
$logger->info('User logged in', ['@i' => 0xABCD1234, 'userId' => 42]);
