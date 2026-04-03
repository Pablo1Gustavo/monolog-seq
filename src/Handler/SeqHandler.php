<?php

declare(strict_types=1);
namespace Pablo1Gustavo\MonologSeq\Handler;

use GuzzleHttp\{Client as GuzzleHttpClient, ClientInterface};
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\{Level, LogRecord};
use Pablo1Gustavo\MonologSeq\Formatter\SeqJsonFormatter;
use Psr\Http\Message\ResponseInterface;

class SeqHandler extends AbstractProcessingHandler
{
    protected readonly string $url;
    protected readonly string $apiKey;
    protected readonly ClientInterface $client;

    public const SEQ_KEY_HEADER = 'X-Seq-ApiKey';
    public const CLEF_CONTENT_TYPE = 'application/vnd.serilog.clef';

    public function __construct(
        string $url,
        string $apiKey,
        Level $level = Level::Debug,
        bool $bubble = true,
        ?ClientInterface $client = null
    ) {
        $this->url = $url;
        $this->apiKey = $apiKey;
        $this->client = $client ?? new GuzzleHttpClient();
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        $this->sendPayload($record->formatted);
    }

    public function handleBatch(array $records): void
    {
        $records = array_filter($records, fn (LogRecord $r) => $this->isHandling($r));

        if (empty($records))
        {
            return;
        }

        $body = implode("\n", array_map(function (LogRecord $record): string
        {
            $record = $this->processRecord($record);

            return $this->getFormatter()->format($record);
        }, $records));

        $this->sendPayload($body);
    }

    private function sendPayload(string $body): void
    {
        $this->client->request('POST', $this->url, [
            'headers' => [
                'Content-Type'       => self::CLEF_CONTENT_TYPE,
                self::SEQ_KEY_HEADER => $this->apiKey,
            ],
            'body' => $body,
        ]);
    }

    protected function getDefaultFormatter(): FormatterInterface
    {
        return new SeqJsonFormatter();
    }
}
