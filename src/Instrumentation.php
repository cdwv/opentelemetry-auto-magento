<?php

declare(strict_types=1);

namespace Codewave\OpenTelemetry\Magento;

use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\SDK\Common\Configuration\Configuration;

class Instrumentation
{
    public const NAME = 'cdwv_magento';

    private static CachedInstrumentation $instance;

    public static function register(): void
    {
        $instrumentation = self::getInstance();

        Hooks\ConsoleApplicationHook::hook($instrumentation);
        Hooks\CommandHook::hook($instrumentation);
        Hooks\BootstrapCreateHook::hook($instrumentation);
        Hooks\BootstrapCreateApplicationHook::hook($instrumentation);
        Hooks\BootstrapRunApplicationHook::hook($instrumentation);
        Hooks\ActionHook::hook($instrumentation);
        Hooks\SessionManagerHook::hook($instrumentation);
        Hooks\LoggerHook::hook($instrumentation);
    }

    public static function shouldTraceCli(): bool
    {
        return PHP_SAPI !== 'cli' || (
            class_exists(Configuration::class)
            && Configuration::getBoolean('OTEL_PHP_TRACE_CLI_ENABLED', false)
        );
    }

    public static function getInstance(): CachedInstrumentation
    {
        if (! isset(self::$instance)) {
            self::$instance = new CachedInstrumentation(
                'com.codewave.opentelemetry.magento',
                null,
                'https://opentelemetry.io/schemas/1.30.0',
            );
        }

        return self::$instance;
    }
}
