<?php

namespace RenokiCo\LaravelExporter\Test;

use RenokiCo\LaravelExporter\Exporter;
use RenokiCo\LaravelExporter\Test\Fixtures\CountingMetric;
use RenokiCo\LaravelExporter\Test\Fixtures\MeasuredJobsMetric;
use RenokiCo\LaravelExporter\Test\Fixtures\TestMetric;

class RunTest extends TestCase
{
    public function test_a_removed_jobs_series_disappears(): void
    {
        Exporter::metrics([MeasuredJobsMetric::class]);

        MeasuredJobsMetric::$jobs = ['App\\Jobs\\Kept' => 1.5, 'App\\Jobs\\Removed' => 2.5];

        $first = Exporter::exportAsPlainText('jobs-metrics');

        $this->assertStringContainsString('laravel_job_runtime{job="App\\\\Jobs\\\\Kept"} 1.5', $first);
        $this->assertStringContainsString('laravel_job_runtime{job="App\\\\Jobs\\\\Removed"} 2.5', $first);

        // The job is no longer measured, so no metric sets its series.
        MeasuredJobsMetric::$jobs = ['App\\Jobs\\Kept' => 3.5];

        $second = Exporter::exportAsPlainText('jobs-metrics');

        $this->assertStringContainsString('laravel_job_runtime{job="App\\\\Jobs\\\\Kept"} 3.5', $second);
        $this->assertStringNotContainsString('Removed', $second);
    }

    public function test_only_the_asked_groups_metrics_are_updated(): void
    {
        Exporter::metrics([TestMetric::class, CountingMetric::class]);

        CountingMetric::$updates = 0;

        $metrics = Exporter::exportAsPlainText('metrics');

        $this->assertStringContainsString('laravel_test_metric', $metrics);
        $this->assertStringNotContainsString('laravel_counting_metric', $metrics);
        $this->assertSame(0, CountingMetric::$updates);

        $counting = Exporter::exportAsPlainText('counting-metrics');

        $this->assertStringContainsString('laravel_counting_metric 1', $counting);
        $this->assertStringNotContainsString('laravel_test_metric', $counting);
        $this->assertSame(1, CountingMetric::$updates);
    }
}
