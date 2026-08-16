<?php

declare(strict_types=1);

namespace AppTest\Container;

use App\Container\NoteListHandlerFactory;
use App\Handler\NoteListHandler;
use AppTest\InMemoryContainer;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\MessageBusInterface;

#[CoversClass(NoteListHandlerFactory::class)]
#[CoversMethod(NoteListHandlerFactory::class, '__invoke')]
final class NoteListHandlerFactoryTest extends TestCase
{
    public function testFactoryReturnsNoteListHandler(): void
    {
        $container = new InMemoryContainer();
        $container->setService(MessageBusInterface::class, $this->createStub(MessageBusInterface::class));
        $container->setService(
            TemplateRendererInterface::class,
            $this->createStub(TemplateRendererInterface::class),
        );

        $factory = new NoteListHandlerFactory();
        $handler = $factory($container);

        self::assertInstanceOf(NoteListHandler::class, $handler);
    }
}
