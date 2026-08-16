<?php

declare(strict_types=1);

namespace AppTest\Listener;

use App\Listener\MessageLifecycleListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Tracy\Debugger;
use Webware\MessageBus\Command\CommandInterface;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\Event\Command\CommandPostHandleEvent;
use Webware\MessageBus\Event\Command\CommandPreHandleEvent;
use Webware\MessageBus\Event\EventInterface;
use Webware\MessageBus\Event\Query\QueryPostHandleEvent;
use Webware\MessageBus\Event\Query\QueryPreHandleEvent;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryInterface;
use Webware\MessageBus\Query\QueryResultInterface;

use function dirname;

#[CoversClass(MessageLifecycleListener::class)]
#[CoversMethod(MessageLifecycleListener::class, '__invoke')]
final class MessageLifecycleListenerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // No container/middleware in a plain unit test, so apply
        // config/autoload/tracy.global.php ourselves.
        $tracyConfig            = require dirname(__DIR__, 3) . '/config/autoload/tracy.global.php';
        Debugger::$logDirectory = $tracyConfig[Debugger::class]['logDirectory'];
    }

    public function testInvokeLogsAllPipelineEventsWithoutThrowing(): void
    {
        $command = $this->createStub(CommandInterface::class);
        $query   = $this->createStub(QueryInterface::class);

        $commandResult = $this->createStub(CommandResultInterface::class);
        $commandResult->method('getCommand')->willReturn($command);
        $commandResult->method('getStatus')->willReturn(MessageStatus::Success);

        $queryResult = $this->createStub(QueryResultInterface::class);
        $queryResult->method('getQuery')->willReturn($query);
        $queryResult->method('getStatus')->willReturn(MessageStatus::Success);

        $listener = new MessageLifecycleListener();
        $listener(new CommandPreHandleEvent($command));
        $listener(new CommandPostHandleEvent($commandResult));
        $listener(new QueryPreHandleEvent($query));
        $listener(new QueryPostHandleEvent($queryResult));
        $listener($this->createStub(EventInterface::class));

        $this->addToAssertionCount(1);
    }
}
