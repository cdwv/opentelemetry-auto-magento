<?php

declare(strict_types=1);

namespace Codewave\OpenTelemetry\Magento\Hooks;

use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SemConv\TraceAttributes;
use Symfony\Component\Console\Command\Command;

use function OpenTelemetry\Instrumentation\hook;

class CommandHook
{
    use MagentoHookTrait;

    protected function hookExecute(): bool
    {
        return hook(
            Command::class,
            'execute',
            pre: function (Command $command, array $params, string $class, string $function, ?string $filename, ?int $lineno) {
                $builder = $this->instrumentation
                    ->tracer()
                    ->spanBuilder(sprintf('Command %s', $command->getName() ?: 'unknown'))
                    ->setAttribute(TraceAttributes::CODE_FUNCTION, $function)
                    ->setAttribute(TraceAttributes::CODE_NAMESPACE, $class)
                    ->setAttribute(TraceAttributes::CODE_FILEPATH, $filename)
                    ->setAttribute(TraceAttributes::CODE_LINENO, $lineno);

                $parent = Context::getCurrent();
                $span = $builder->startSpan();
                Context::storage()->attach($span->storeInContext($parent));

                return $params;

            },
            post: function (Command $command, array $params, ?int $exitCode, ?Throwable $exception): void {
                $scope = Context::storage()->scope();
                if (! $scope) {
                    return;
                }

                $span = Span::fromContext($scope->context());
                $span->addEvent('command finished', [
                    'exit-code' => $exitCode,
                ]);

                $this->endSpan($exception);
            }
        );
    }
}
