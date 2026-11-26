<?php

namespace RenokiCo\LaravelExporter;

use Prometheus\CollectorRegistry;
use Prometheus\Exception\MetricsRegistrationException;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory;

class Exporter
{
    /**
     * The metrics to register.
     *
     * @var array
     */
    protected static array $metrics = [
        //
    ];

    /**
     * The registered metrics.
     *
     * @var array
     */
    protected static array $registeredMetrics = [
        //
    ];

    /**
     * The Collector Registries for groups.
     *
     * @var \Prometheus\CollectorRegistry[]
     */
    protected static array $registries = [
        //
    ];

    /**
     * A list, keyed by group names, whose values are strings
     * that will be sent as response.
     *
     * @var array
     */
    protected static array $plainTextResponses = [
        //
    ];

    /**
     * Set the registry.
     *
     * @param  \Prometheus\CollectorRegistry  $collectorRegistry
     * @param  string  $group
     * @return void
     */
    public static function setRegistry(CollectorRegistry $registry, string $group = 'metrics')
    {
        self::$registries[$group] = $registry;
    }

    /**
     * Add a metric to the registrable metrics.
     *
     * @param  string  $class
     * @return void
     */
    public static function register(string $class)
    {
        if (in_array($class, static::$metrics)) {
            return;
        }

        static::$metrics[] = $class;
    }

    /**
     * Set the metrics value.
     *
     * @param  array  $classes
     * @return void
     */
    public static function metrics(array $classes)
    {
        static::$metrics = [];

        foreach ($classes as $class) {
            /** @var string $class */
            static::register($class);
        }
    }

    /**
     * Export a string as response to a group instead of the computed
     * metrics by the given collectors.
     *
     * @param  string|callable  $handler
     * @param  string|callable  $group
     * @return void
     */
    public static function exportResponse(string|callable $handler, string $group = 'metrics')
    {
        static::$plainTextResponses[$group] = $handler;
    }

    /**
     * Flush responses.
     *
     * @retunr void
     */
    public static function flushResponses()
    {
        static::$plainTextResponses = [];
    }

    /**
     * Add the group's registered metrics to its Prometheus registry.
     *
     * Only the metrics shown on the group are updated. A registry set with setRegistry() keeps its
     * series between runs; otherwise each run gets a fresh one, so a series no metric sets any more
     * (a job no longer measured) is not rendered again. A static registry would keep every series an
     * Octane worker ever set.
     *
     * @param  string  $group
     * @return \Prometheus\CollectorRegistry
     */
    public static function run(string $group = 'metrics')
    {
        $registry = static::$registries[$group] ?? new CollectorRegistry(new InMemory);

        foreach (static::$metrics as $metricClass) {
            if ($metricClass::$showsOnGroup !== $group) {
                continue;
            }

            try {
                $metric = new $metricClass($registry);

                /** @var \RenokiCo\LaravelExporter\Metric $metric */
                $metric->update();
            } catch (MetricsRegistrationException $e) {
                /** @var \RenokiCo\LaravelExporter\Metric|null $metric */
                $metric = static::$registeredMetrics[$metricClass] ?? null;

                if ($metric) {
                    $metric->update();
                }
            }

            static::$registeredMetrics[$metricClass] = $metric;
        }

        return $registry;
    }

    /**
     * Export the metrics as plaintext.
     *
     * @param  string  $group
     * @return string
     */
    public static function exportAsPlainText(string $group = 'metrics'): string
    {
        if ($handler = static::$plainTextResponses[$group] ?? false) {
            return is_callable($handler) ? $handler() : $handler;
        }

        return (new RenderTextFormat)->render(
            static::run($group)->getMetricFamilySamples()
        );
    }

    /**
     * Get the registered metric.
     *
     * @param  string  $class
     * @return \RenokiCo\LaravelExporter\Metric|null
     */
    public static function metric(string $class)
    {
        static::run($class::$showsOnGroup);

        return static::$registeredMetrics[$class] ?? null;
    }
}
