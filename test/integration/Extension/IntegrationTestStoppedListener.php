<?php

declare(strict_types=1);

namespace IntegrationTest\Extension;

use PHPUnit\Event\TestSuite\Finished;
use PHPUnit\Event\TestSuite\FinishedSubscriber;

use function printf;

final class IntegrationTestStoppedListener implements FinishedSubscriber
{
    private const string TESTSUITE_NAME = 'integration';

    public function notify(Finished $event): void
    {
        if ($event->testSuite()->name() !== self::TESTSUITE_NAME) {
            return;
        }

        // Notes are left in place (not dropped/truncated) so they can be
        // inspected afterwards, e.g. via phpMyAdmin at :8082.
        printf("\nIntegration test suite finished.\n");
    }
}
