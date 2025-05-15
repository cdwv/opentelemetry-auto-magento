# OpenTelemetry Auto Instrumentation for Magento 2 Framework

This package provides minimal auto-instrumentation for Magento 2 application.

Work is still in progress, so all the span names can (and probably will) change.

Currently it has the ability to provide some basic instrumentation for several of the main components
of Magento 2 stack. 

# Features

At this point, there are only some basic hooks to instrument critical points of the request flow.

For specific integrations, please refer to other auto-instrumentations.

# Configuration

This package doesn't contain any specific configuration. Due to specifics of Magento bootstrap process
it doesn't make much sense to provide configuration through Magento control panel – it leaves some
parts of the request uninstrumented.

All the configuration variables that are described in [OpenTelemetry Environment Variable Specification](https://opentelemetry.io/docs/specs/otel/configuration/sdk-environment-variables/)
and [PHP SDK Specification](https://opentelemetry.io/docs/languages/php/sdk/) should be available, if the appropriate packages are installed.

```
OTEL_SERVICE_NAME=magento_service
OTEL_PHP_AUTOLOAD_ENABLED=true
OTEL_PHP_TRACE_CLI_ENABLED=true
OTEL_EXPORTER=otlp
OTEL_EXPORTER_OTLP_ENDPOINT=https://otlp-exporter-endpoint
OTEL_EXPORTER_OTLP_HEADERS=authorization=Basic dXNlcm5hbWU6cGFzc3dvcmQ= 
OTEL_EXPORTER_OTLP_TRACES_PROTOCOL=http/json
OTEL_PROPAGATORS=tracecontext
OTEL_TRACES_SAMPLER=parentbased_always_on
```
