<?php

declare(strict_types=1);

namespace Codewave\OpenTelemetry\Magento\Hooks;

use Magento\Framework\Session\SessionManager;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SemConv\TraceAttributes;

use function OpenTelemetry\Instrumentation\hook;

class SessionManagerHook
{
    use MagentoHookTrait;

    protected function hookExecute(): bool
    {
        return hook(
            SessionManager::class,
            'writeClose',
            pre: function (SessionManager $sessionManager, array $params, string $class, string $function, ?string $filename, ?int $lineno) {
                $builder = $this->instrumentation
                    ->tracer()
                    ->spanBuilder('SessionManager::writeClose')
                    ->setAttribute(TraceAttributes::CODE_FUNCTION, $function)
                    ->setAttribute(TraceAttributes::CODE_NAMESPACE, $class)
                    ->setAttribute(TraceAttributes::CODE_FILEPATH, $filename)
                    ->setAttribute(TraceAttributes::CODE_LINENO, $lineno);

                $parent = Context::getCurrent();
                $span = $builder->startSpan();
                Context::storage()->attach($span->storeInContext($parent));

                return $params;

            },
            post: function (SessionManager $sessionManager, array $params, $returnValue, ?Throwable $exception): void {
                $this->endSpan($exception);
            }
        );
    }
}
