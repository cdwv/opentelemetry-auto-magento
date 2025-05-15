<?php

declare(strict_types=1);

namespace Codewave\OpenTelemetry\Magento\Hooks;

use Magento\Framework\App\Bootstrap;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Trace\AutoRootSpan;
use OpenTelemetry\SemConv\TraceAttributes;

use function OpenTelemetry\Instrumentation\hook;

class BootstrapCreateHook
{
    use MagentoHookTrait;

    protected function hookExecute(): bool
    {
        return hook(
            Bootstrap::class,
            'create',
            pre: function ($className, array $params, string $class, string $function, ?string $filename, ?int $lineno) {
                $request = AutoRootSpan::createRequest();

                if ($request) {
                    AutoRootSpan::create($request);
                    AutoRootSpan::registerShutdownHandler();
                }

                $builder = $this->instrumentation
                    ->tracer()
                    ->spanBuilder('Bootstrap::create')
                    ->setAttribute(TraceAttributes::CODE_FUNCTION, $function)
                    ->setAttribute(TraceAttributes::CODE_NAMESPACE, $class)
                    ->setAttribute(TraceAttributes::CODE_FILEPATH, $filename)
                    ->setAttribute(TraceAttributes::CODE_LINENO, $lineno);

                $parent = Context::getCurrent();
                $span = $builder->startSpan();
                Context::storage()->attach($span->storeInContext($parent));

                return $params;

            },
            post: function ($className, array $params, $returnValue, ?Throwable $exception): void {
                $this->endSpan($exception);
            }
        );
    }

    private static function registerAutoRootSpan() {}
}
