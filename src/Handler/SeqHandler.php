<?php

declare(strict_types=1);
namespace Pablo1Gustavo\MonologSeq\Handler;

use GuzzleHttp\ClientInterface;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\{Level, LogRecord};
use Pablo1Gustavo\MonologSeq\Exception\SeqDeliveryException;
use Pablo1Gustavo\MonologSeq\Formatter\SeqJsonFormatter;
use Pablo1Gustavo\MonologSeq\Http\RetryMiddleware;

class SeqHandler extends AbstractProcessingHandler
{
    protected readonly string $url;
    protected readonly ?string $apiKey;
    protected readonly ClientInterface $client;
    protected readonly int $maxRetries;

    public const SEQ_KEY_HEADER = 'X-Seq-ApiKey';
    public const CLEF_CONTENT_TYPE = 'application/vnd.serilog.clef';

    public function __construct(
        string $url,
        ?string $apiKey = null,
        Level $level = Level::Debug,
        bool $bubble = true,
        ?ClientInterface $client = null,
        int $maxRetries = 3,
    ) {
        if (filter_var($url, FILTER_VALIDATE_URL) === false)
        {
            throw new \InvalidArgumentException("Invalid Seq URL: \"{$url}\".");
        }
        if ($maxRetries < 0)
        {
            throw new \InvalidArgumentException("Max retries must be >= 0, got {$maxRetries}.");
        }

        $this->url = $url;
        $this->apiKey = $apiKey;
        $this->maxRetries = $maxRetries;
        $this->client = $client ?? RetryMiddleware::buildClient($maxRetries);
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
        $headers = ['Content-Type' => self::CLEF_CONTENT_TYPE];

        if (!empty($this->apiKey))
        {
            $headers[self::SEQ_KEY_HEADER] = $this->apiKey;
        }

        try
        {
            $this->client->request('POST', $this->url, [
                'headers' => $headers,
                'body'    => $body,
            ]);
        } catch (\Throwable $e)
        {
            throw SeqDeliveryException::sendFailed($this->maxRetries, $e);
        }
    }

    protected function getDefaultFormatter(): FormatterInterface
    {
        return new SeqJsonFormatter();
    }
}
