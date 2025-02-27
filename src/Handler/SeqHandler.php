<?php
declare(strict_types = 1);

namespace Pablo\MonologSeq\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\MissingExtensionException;
use Monolog\Level;
use Monolog\LogRecord;

class SeqHandler extends AbstractProcessingHandler
{
    protected readonly string $url;
    protected readonly string $apiKey;

    const SEQ_KEY_HEADER = 'X-Seq-Api-Key';

    public function __construct(
        string $url,
        string $apiKey,
        Level $level = Level::Info,
        bool $bubble = true 
    )
    {
        if (!\extension_loaded('curl'))
        {
            throw new MissingExtensionException('The curl extension is required to use the SeqHandler');
        }
        $this->url = $url;
        $this->apiKey = $apiKey;
        parent::__construct($level, $bubble);
    }

    public function write(LogRecord $record): void
    {

    }
}