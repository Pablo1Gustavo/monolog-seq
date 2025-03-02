<?php
declare(strict_types=1);
namespace Pablo1Gustavo\MonologSeq\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\Curl\Util as CurlUtil;
use Monolog\Handler\MissingExtensionException;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Formatter\FormatterInterface;
use Pablo1Gustavo\MonologSeq\Formatter\SeqJsonFormatter;

class SeqHandler extends AbstractProcessingHandler
{
    protected readonly string $url;
    protected readonly string $apiKey;

    const SEQ_KEY_HEADER = 'X-Seq-Api-Key';
    const CLEF_CONTENT_TYPE = 'application/vnd.serilog.clef';

    public function __construct(
        string $url,
        string $apiKey,
        Level $level = Level::Debug,
        bool $bubble = true
    ) {
        if (!\extension_loaded('curl')) {
            throw new MissingExtensionException('The curl extension is required to use the SeqHandler');
        }
        $this->url = $url;
        $this->apiKey = $apiKey;
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        $this->send($record->formatted);
    }

    protected function send(string $data): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: ' . self::CLEF_CONTENT_TYPE,
            'X-Seq-ApiKey: ' . $this->apiKey,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        CurlUtil::execute($ch);
    }

    protected function getDefaultFormatter(): FormatterInterface
    {
        return new SeqJsonFormatter();
    }
}
