<?php

declare(strict_types=1);

use Codewave\OpenTelemetry\Magento\Instrumentation;
use OpenTelemetry\SDK\Sdk;

if (class_exists(Sdk::class) && Sdk::isInstrumentationDisabled(Instrumentation::NAME) === true) {
    return;
}

if (extension_loaded('opentelemetry') === false) {
    trigger_error('The opentelemetry extension must be loaded in order to autoload the OpenTelemetry Magento auto-instrumentation', E_USER_WARNING);

    return;
}

Instrumentation::register();
