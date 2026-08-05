<?php

declare(strict_types=1);

namespace AppTest\Container;

use App\Container\NoteCommandHandlerFactory;
use App\Handler\NoteCommandHandler;
use AppTest\InMemoryContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\MessageBusInterface;

#[CoversClass(NoteCommandHandlerFactory::class)]
#[CoversMethod(NoteCommandHandlerFactory::class, '__invoke')]
final class NoteCommandHandlerFactoryTest extends TestCase
{
    public function testFactoryReturnsNoteCommandHandler(): void
    {
        $container = new InMemoryContainer();
        $container->setService(MessageBusInterface::class, $this->createStub(MessageBusInterface::class));

        $factory = new NoteCommandHandlerFactory();
        $handler = $factory($container);

        self::assertInstanceOf(NoteCommandHandler::class, $handler);
    }
}
