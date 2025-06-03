<?php

declare(strict_types=1);

namespace Codewave\OpenTelemetry\Magento\Hooks;

use Codewave\OpenTelemetry\Magento\Trace\AutoRootSpan;
use Codewave\OpenTelemetry\Magento\Trace\CliAutoRootSpan;
use Magento\Framework\App\Bootstrap;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SemConv\TraceAttributes;
use Throwable;

use function OpenTelemetry\Instrumentation\hook;

class BootstrapPopulateAutoloaderHook
{
    use MagentoHookTrait;

    private $rootSpanRegistered = false;

    protected function hookExecute(): bool
    {
        return hook(
            Bootstrap::class,
            'populateAutoloader',
            pre: function ($className, array $params, string $class, string $function, ?string $filename, ?int $lineno) {
                $this->registerRootSpan();

                $builder = $this->instrumentation
                    ->tracer()
                    ->spanBuilder('Bootstrap::populateAutoloader')
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

    private function registerRootSpan()
    {
        if ($this->rootSpanRegistered) {
            return;
        }

        if (! empty($_SERVER['REQUEST_METHOD'] ?? null)) {
            $request = AutoRootSpan::createRequest();

            if ($request) {
                $this->rootSpanRegistered = true;
                AutoRootSpan::create($request);
                AutoRootSpan::registerShutdownHandler();
            }

        }
        if (! empty($_SERVER['argv'] ?? null)) {
            $command = CliAutoRootSpan::createCommand();

            if ($command) {
                CliAutoRootSpan::create($command);
                CliAutoRootSpan::registerShutdownHandler();
            }
        }
    }
}
