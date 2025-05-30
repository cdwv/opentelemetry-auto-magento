<?php

namespace Codewave\OpenTelemetry\Magento\Trace;

use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\API\Common\Time\ClockInterface;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Common\Util\ShutdownHandler;
use OpenTelemetry\SemConv\Version;

class CliAutoRootSpan
{
    use LogsMessagesTrait;

    public static function create(CliRootSpanAttributes $attributes)
    {
        $tracer = Globals::tracerProvider()->getTracer(
            'com.codewave.opentelemetry.magento.auto-root-span',
            null,
            Version::VERSION_1_25_0->url(),
        );
        $parent = Globals::propagator()->extract($_SERVER);
        $startTime = (int) microtime(true);
        $span = $tracer->spanBuilder($attributes->command)
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->setStartTimestamp((int) ($startTime * ClockInterface::NANOS_PER_SECOND))
            ->startSpan();

        Context::storage()->attach($span->storeInContext($parent));
    }

    public static function createCommand(): CliRootSpanAttributes
    {
        $attributes = new CliRootSpanAttributes;
        $command = $_SERVER['argv'][0];
        $args = array_slice($_SERVER['argv'], 1);
        $attributes->command = 'CLI '.$command.' '.implode(' ', $args);

        return $attributes;
    }

    /**
     * @internal
     */
    public static function registerShutdownHandler(): void
    {
        ShutdownHandler::register(self::shutdownHandler(...));
    }

    /**
     * @internal
     */
    public static function shutdownHandler(): void
    {
        $scope = Context::storage()->scope();
        if (! $scope) {
            return;
        }
        $scope->detach();
        $span = Span::fromContext($scope->context());
        $span->end();
    }
}
