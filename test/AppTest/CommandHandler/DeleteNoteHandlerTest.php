<?php

declare(strict_types=1);

namespace AppTest\CommandHandler;

use App\Command\DeleteNoteCommand;
use App\Command\NoteCommandResult;
use App\CommandHandler\DeleteNoteHandler;
use PhpDb\TableGateway\TableGateway;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\MessageStatus;

#[CoversClass(DeleteNoteHandler::class)]
#[CoversMethod(DeleteNoteHandler::class, 'handle')]
final class DeleteNoteHandlerTest extends TestCase
{
    public function testHandleReturnsFailureWhenNoRowAffected(): void
    {
        $gateway = $this->createStub(TableGateway::class);
        $gateway->method('deleteWith')->willReturn(0);

        $handler = new DeleteNoteHandler($gateway);
        $result  = $handler->handle(new DeleteNoteCommand('999'));

        self::assertSame(MessageStatus::Failure, $result->getStatus());
    }

    public function testHandleReturnsSuccessWhenRowAffected(): void
    {
        $gateway = $this->createStub(TableGateway::class);
        $gateway->method('deleteWith')->willReturn(1);

        $handler = new DeleteNoteHandler($gateway);
        $result  = $handler->handle(new DeleteNoteCommand('1'));

        self::assertInstanceOf(NoteCommandResult::class, $result);
        self::assertSame(MessageStatus::Success, $result->getStatus());
        self::assertSame(['id' => '1'], $result->getResult());
    }
}
