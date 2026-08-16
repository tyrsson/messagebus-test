<?php

declare(strict_types=1);

namespace IntegrationTest\Extension;

use App\Ddl\NotesTable;
use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Event\TestSuite\Started;
use PHPUnit\Event\TestSuite\StartedSubscriber;
use Tracy\Debugger;
use Webware\Traccio\Middleware\TracyDebuggerMiddleware;

use function dirname;
use function printf;

final class IntegrationTestStartedListener implements StartedSubscriber
{
    private const string TESTSUITE_NAME = 'integration';

    public function notify(Started $event): void
    {
        if ($event->testSuite()->name() !== self::TESTSUITE_NAME) {
            return;
        }

        printf("\nIntegration test suite started - ensuring notes table exists.\n");

        /** @var \Psr\Container\ContainerInterface $container */
        $container = require dirname(__DIR__, 3) . '/config/container.php';

        // The integration suite dispatches through the MessageBus directly, bypassing
        // TracyDebuggerMiddleware, so apply config/autoload/tracy.global.php ourselves
        // the same way the middleware does.
        $tracyConfig = $container->get('config')[Debugger::class] ?? [];
        if (isset($tracyConfig[TracyDebuggerMiddleware::ENABLE_KEY])) {
            Debugger::enable($tracyConfig[TracyDebuggerMiddleware::ENABLE_KEY]);
        }
        foreach ($tracyConfig as $key => $value) {
            if ($key === TracyDebuggerMiddleware::ENABLE_KEY) {
                continue;
            }
            Debugger::$$key = $value;
        }

        $adapter = $container->get(AdapterInterface::class);

        // The notes table shape evolves (e.g. the body column added later), so
        // recreate it per suite run for determinism.
        $adapter->query('DROP TABLE IF EXISTS notes', AdapterInterface::QUERY_MODE_EXECUTE);

        NotesTable::createIfNotExists($adapter);
    }
}
