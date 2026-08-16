<?php

declare(strict_types=1);

namespace AppTest\CommandHandler;

use App\Command\CreateNoteCommand;
use App\CommandHandler\CreateNoteHandler;
use App\Event\NoteCreatedEvent;
use PhpDb\TableGateway\TableGateway;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageStatus;

#[CoversClass(CreateNoteHandler::class)]
#[CoversMethod(CreateNoteHandler::class, 'createNoteCommand')]
final class CreateNoteHandlerTest extends TestCase
{
    public function testCreateNoteInsertsDispatchesEventAndReturnsSuccessResult(): void
    {
        $gateway = $this->createMock(TableGateway::class);
        $gateway->expects(self::once())->method('insertWith');
        $gateway->method('getLastInsertValue')->willReturn(1);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(NoteCreatedEvent::class));

        $handler = new CreateNoteHandler($gateway, $dispatcher);
        $result  = $handler->createNoteCommand(new CreateNoteCommand('first note', 'note body'));

        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(MessageStatus::Success, $result->getStatus());
        self::assertSame(['id' => 1, 'title' => 'first note'], $result->getResult());
    }

    public function testCreateNoteThrowsWhenLastInsertValueIsUnavailable(): void
    {
        $gateway = $this->createStub(TableGateway::class);
        $gateway->method('getLastInsertValue')->willReturn(false);

        $dispatcher = $this->createStub(EventDispatcherInterface::class);

        $handler = new CreateNoteHandler($gateway, $dispatcher);

        $this->expectException(RuntimeException::class);
        $handler->createNoteCommand(new CreateNoteCommand('first note'));
    }
}
