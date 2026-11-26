<?php

namespace RenokiCo\LaravelExporter\Test\Fixtures;

use RenokiCo\LaravelExporter\GaugeMetric;

class CountingMetric extends GaugeMetric
{
    /**
     * How many times update() ran.
     *
     * @var int
     */
    public static $updates = 0;

    /**
     * The group this metric gets shown into.
     *
     * @var string|null
     */
    public static $showsOnGroup = 'counting-metrics';

    /**
     * Perform the update call on the collector.
     *
     * @return void
     */
    public function update(): void
    {
        static::$updates++;

        $this->set(value: static::$updates);
    }

    /**
     * Get the metric name.
     *
     * @return string
     */
    protected function name(): string
    {
        return 'counting_metric';
    }
}
