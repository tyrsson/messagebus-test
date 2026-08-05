<?php

declare(strict_types=1);

namespace AppTest\Container;

use App\Container\NoteQueryHandlerFactory;
use App\Handler\NoteQueryHandler;
use AppTest\InMemoryContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\MessageBusInterface;

#[CoversClass(NoteQueryHandlerFactory::class)]
#[CoversMethod(NoteQueryHandlerFactory::class, '__invoke')]
final class NoteQueryHandlerFactoryTest extends TestCase
{
    public function testFactoryReturnsNoteQueryHandler(): void
    {
        $container = new InMemoryContainer();
        $container->setService(MessageBusInterface::class, $this->createStub(MessageBusInterface::class));

        $factory = new NoteQueryHandlerFactory();
        $handler = $factory($container);

        self::assertInstanceOf(NoteQueryHandler::class, $handler);
    }
}
