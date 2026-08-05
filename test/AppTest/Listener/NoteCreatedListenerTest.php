<?php

declare(strict_types=1);

namespace AppTest\Listener;

use App\Listener\NoteCreatedListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Tracy\Debugger;
use Webware\MessageBus\Event\EventInterface;

use function dirname;

#[CoversClass(NoteCreatedListener::class)]
#[CoversMethod(NoteCreatedListener::class, '__invoke')]
final class NoteCreatedListenerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // No container/middleware in a plain unit test, so apply
        // config/autoload/tracy.global.php ourselves.
        $tracyConfig            = require dirname(__DIR__, 3) . '/config/autoload/tracy.global.php';
        Debugger::$logDirectory = $tracyConfig[Debugger::class]['logDirectory'];
    }

    public function testInvokeLogsWithoutThrowing(): void
    {
        $event = $this->createStub(EventInterface::class);
        $event->method('getParam')
            ->willReturnMap([
                ['id',    null, 1],
                ['title', null, 'first note'],
            ]);

        $listener = new NoteCreatedListener();
        $listener($event);

        $this->addToAssertionCount(1);
    }
}
