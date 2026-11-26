<?php

namespace RenokiCo\LaravelExporter\Test\Fixtures;

use RenokiCo\LaravelExporter\GaugeMetric;

class MeasuredJobsMetric extends GaugeMetric
{
    /**
     * The runtime of each measured job, by job name.
     *
     * @var array<string, float>
     */
    public static $jobs = [];

    /**
     * The group this metric gets shown into.
     *
     * @var string|null
     */
    public static $showsOnGroup = 'jobs-metrics';

    /**
     * Perform the update call on the collector.
     *
     * @return void
     */
    public function update(): void
    {
        foreach (static::$jobs as $job => $runtime) {
            $this->set(
                value: $runtime,
                labels: ['job' => $job],
            );
        }
    }

    /**
     * Get the metric name.
     *
     * @return string
     */
    protected function name(): string
    {
        return 'job_runtime';
    }

    /**
     * Get the metric allowed labels.
     *
     * @return array
     */
    protected function allowedLabels(): array
    {
        return [
            'job',
        ];
    }
}
