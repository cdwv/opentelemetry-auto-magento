<?php

declare(strict_types=1);

namespace Codewave\OpenTelemetry\Magento\Hooks;

use Magento\Framework\App\Action\Action;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SemConv\TraceAttributes;

use function OpenTelemetry\Instrumentation\hook;

class ActionHook
{
    use MagentoHookTrait;

    protected function hookExecute(): bool
    {
        return hook(
            Action::class,
            'dispatch',
            pre: function (Action $action, array $params, string $class, string $function, ?string $filename, ?int $lineno) {
                $request = $params[0];
                $builder = $this->instrumentation
                    ->tracer()
                    ->spanBuilder(sprintf('Action %s', $request->getFullActionName() ?: 'unknown'))
                    ->setAttribute(TraceAttributes::CODE_FUNCTION, $function)
                    ->setAttribute(TraceAttributes::CODE_NAMESPACE, $class)
                    ->setAttribute(TraceAttributes::CODE_FILEPATH, $filename)
                    ->setAttribute(TraceAttributes::CODE_LINENO, $lineno);

                $parent = Context::getCurrent();
                $span = $builder->startSpan();
                Context::storage()->attach($span->storeInContext($parent));

                return $params;

            },
            post: function (Action $action, array $params, $returnValue, ?Throwable $exception): void {
                $this->endSpan($exception);
            }
        );
    }
}
