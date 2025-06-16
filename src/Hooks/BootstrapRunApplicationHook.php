<?php

declare(strict_types=1);

namespace Codewave\OpenTelemetry\Magento\Hooks;

use Magento\Framework\App\Bootstrap;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SemConv\TraceAttributes;
use Throwable;

use function OpenTelemetry\Instrumentation\hook;

class BootstrapRunApplicationHook
{
    use MagentoHookTrait;

    protected function hookExecute(): bool
    {
        return hook(
            Bootstrap::class,
            'run',
            pre: function (Bootstrap $bootstrap, array $params, string $class, string $function, ?string $filename, ?int $lineno) {
                $builder = $this->instrumentation
                    ->tracer()
                    ->spanBuilder('Bootstrap::run')
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

                /**
                 * Finish request to not introduce an extra latency
                 * before OpenTelemetry module sends all the span
                 * data to the collector.
                 */
                if (\function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                } elseif (\function_exists('litespeed_finish_request')) {
                    litespeed_finish_request();
                } elseif (! \in_array(\PHP_SAPI, ['cli', 'phpdbg', 'embed'], true)) {
                    static::closeOutputBuffers(0, true);
                    flush();
                }
            }
        );
    }

    public static function closeOutputBuffers(int $targetLevel, bool $flush): void
    {
        $status = ob_get_status(true);
        $level = \count($status);
        $flags = \PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? \PHP_OUTPUT_HANDLER_FLUSHABLE : \PHP_OUTPUT_HANDLER_CLEANABLE);

        while ($level-- > $targetLevel && ($s = $status[$level]) && (!isset($s['del']) ? !isset($s['flags']) || ($s['flags'] & $flags) === $flags : $s['del'])) {
            if ($flush) {
                ob_end_flush();
            } else {
                ob_end_clean();
            }
        }
    }
}
