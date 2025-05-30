<?php

declare(strict_types=1);

namespace Codewave\OpenTelemetry\Magento\Hooks;

use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SemConv\TraceAttributes;
use Throwable;

trait MagentoHookTrait
{
    private static $instance;

    protected function __construct(
        protected CachedInstrumentation $instrumentation,
    ) {}

    public static function hook(CachedInstrumentation $instrumentation)
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (! isset(self::$instance)) {
            /** @phan-suppress-next-line PhanTypeInstantiateTraitStaticOrSelf,PhanTypeMismatchPropertyReal */
            self::$instance = new self($instrumentation);
            self::$instance->instrument();
        }

        return self::$instance;
    }

    public function instrument(): void
    {
        $this->hookExecute();
    }

    protected function endSpan(?Throwable $exception = null): void
    {
        $scope = Context::storage()->scope();
        if (! $scope) {
            return;
        }

        $scope->detach();
        $span = Span::fromContext($scope->context());

        if ($exception) {
            $span->recordException($exception, [
                TraceAttributes::EXCEPTION_ESCAPED => true,
            ]);
            $span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
        }

        $span->end();
    }
}
