<?php

declare(strict_types=1);

namespace AppTest\CommandHandler;

use App\Command\CreateNoteCommand;
use App\Command\NoteCommandResult;
use App\CommandHandler\CreateNoteHandler;
use App\Event\NoteCreatedEvent;
use PhpDb\TableGateway\TableGateway;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webware\MessageBus\MessageStatus;

#[CoversClass(CreateNoteHandler::class)]
#[CoversMethod(CreateNoteHandler::class, 'handle')]
final class CreateNoteHandlerTest extends TestCase
{
    public function testHandleInsertsAndReturnsSuccessResultWithEvent(): void
    {
        $gateway = $this->createMock(TableGateway::class);
        $gateway->expects(self::once())->method('insertWith');
        $gateway->method('getLastInsertValue')->willReturn(1);

        $handler = new CreateNoteHandler($gateway);
        $result  = $handler->handle(new CreateNoteCommand('first note'));

        self::assertInstanceOf(NoteCommandResult::class, $result);
        self::assertSame(MessageStatus::Success, $result->getStatus());
        self::assertSame(['id' => 1, 'title' => 'first note'], $result->getResult());
        self::assertInstanceOf(NoteCreatedEvent::class, $result->getEvent());
    }

    public function testHandleThrowsWhenLastInsertValueIsUnavailable(): void
    {
        $gateway = $this->createStub(TableGateway::class);
        $gateway->method('getLastInsertValue')->willReturn(false);

        $handler = new CreateNoteHandler($gateway);

        $this->expectException(RuntimeException::class);
        $handler->handle(new CreateNoteCommand('first note'));
    }
}
