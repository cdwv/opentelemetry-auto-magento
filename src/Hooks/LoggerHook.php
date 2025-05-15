<?php

declare(strict_types=1);

namespace Codewave\OpenTelemetry\Magento\Hooks;

use Monolog\Logger;
use OpenTelemetry\API\Logs\LogRecord;

use function OpenTelemetry\Instrumentation\hook;

class LoggerHook
{
    use MagentoHookTrait;

    private array $logHashes = [];

    protected function hookExecute(): bool
    {
        return hook(
            Logger::class,
            'addRecord',
            pre: function (Logger $logger, array $params, string $class, string $function, ?string $filename, ?int $lineno) {
                [$level, $message, $context, $datetime] = array_pad($params, 4, null);
                $hash = md5(serialize([$level, $message, $context, $datetime]));
                $this->logHashes[$hash] = 1 + ($this->logHashes[$hash] ?? 0);

                if ($this->logHashes[$hash] == 1) {
                    $logger = $this->instrumentation->logger();

                    $record = (new LogRecord($message))
                        ->setSeverityText(Logger::getLevelName($level))
                        ->setSeverityNumber($level)
                        ->setAttributes($context);

                    $logger->emit($record);

                }

                return $params;
            });
    }
}
