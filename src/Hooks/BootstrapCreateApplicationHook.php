<?php

declare(strict_types=1);

namespace Codewave\OpenTelemetry\Magento\Hooks;

use Magento\Framework\App\Bootstrap;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SemConv\TraceAttributes;
use Throwable;

use function OpenTelemetry\Instrumentation\hook;

class BootstrapCreateApplicationHook
{
    use MagentoHookTrait;

    protected function hookExecute(): bool
    {
        return hook(
            Bootstrap::class,
            'createApplication',
            pre: function (Bootstrap $bootstrap, array $params, string $class, string $function, ?string $filename, ?int $lineno) {
                $builder = $this->instrumentation
                    ->tracer()
                    ->spanBuilder('Bootstrap::createApplication')
                    ->setAttribute(TraceAttributes::CODE_FUNCTION, $function)
                    ->setAttribute(TraceAttributes::CODE_NAMESPACE, $class)
                    ->setAttribute(TraceAttributes::CODE_FILEPATH, $filename)
                    ->setAttribute(TraceAttributes::CODE_LINENO, $lineno);

                $parent = Context::getCurrent();
                $span = $builder->startSpan();
                Context::storage()->attach($span->storeInContext($parent));

                return $params;

            },
            post: function (Bootstrap $bootstrap, array $params, $returnValue, ?Throwable $exception): void {
                $this->endSpan($exception);
            }
        );
    }
}
