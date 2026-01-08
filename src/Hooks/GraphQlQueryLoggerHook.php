<?php

declare(strict_types=1);

namespace Codewave\OpenTelemetry\Magento\Hooks;

use Magento\GraphQl\Model\Query\Logger\LoggerInterface;
use Magento\GraphQl\Model\Query\Logger\LoggerPool;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\SemConv\TraceAttributes;

use function OpenTelemetry\Instrumentation\hook;

class GraphQlQueryLoggerHook
{
    use MagentoHookTrait;

    private const GRAPHQL_OPERATION_NAMES = 'graphql.operation.names';
    private const GRAPHQL_OPERATION_NUMBER = 'graphql.operation.number';

    protected function hookExecute(): bool
    {
        return hook(
            LoggerPool::class,
            'execute',
            pre: function (LoggerPool $action, array $params, string $class, string $function, ?string $filename, ?int $lineno) {
                $queryDetails = $params[0];

                $parentSpan = Span::getCurrent();
                if ($parentSpan->isRecording() && $parentSpan->getName() === GraphQlControllerHook::SPAN_NAME) {
                    $parentSpan
                        ->updateName(sprintf('GraphQl %s', $queryDetails[LoggerInterface::TOP_LEVEL_OPERATION_NAME]))
                        ->setAttribute(TraceAttributes::GRAPHQL_OPERATION_NAME, $queryDetails[LoggerInterface::TOP_LEVEL_OPERATION_NAME])
                        ->setAttribute(self::GRAPHQL_OPERATION_NAMES, $queryDetails[LoggerInterface::OPERATION_NAMES] ?? "unknown")
                        ->setAttribute(self::GRAPHQL_OPERATION_NUMBER, $queryDetails[LoggerInterface::NUMBER_OF_OPERATIONS] ?? "unknown");
                }

                return $params;

            }
        );
    }
}
