<?php
use Monolog\Logger;
use Pablo1Gustavo\MonologSeq\Handler\SeqHandler;

require_once __DIR__ . '/vendor/autoload.php';

$seqUrl = "http://localhost:5341/api/events/raw";
$seqApiKey = "H16XK1wLgC0LDsen5fwA";

$logger = new Logger('seq');
$logger->pushHandler(new SeqHandler($seqUrl, $seqApiKey));

$logger->critical('error', ['excepasdtion' => new Exception('error')]);
$logger->warning('warn message', ['abc' => "123", 'def' => [1,2,3]]); 
$logger->info("hello my name is {name}", ['name' => 'pablo']);
$logger->debug("debug message", ['date' => new DateTime("2002-01-13")]);
$logger->emergency("teste");

