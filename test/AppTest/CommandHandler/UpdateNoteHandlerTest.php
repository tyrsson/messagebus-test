<?php

declare(strict_types=1);

namespace AppTest\CommandHandler;

use App\Command\UpdateNoteCommand;
use App\CommandHandler\UpdateNoteHandler;
use PhpDb\TableGateway\TableGateway;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageStatus;

#[CoversClass(UpdateNoteHandler::class)]
#[CoversMethod(UpdateNoteHandler::class, 'updateNoteCommand')]
final class UpdateNoteHandlerTest extends TestCase
{
    public function testUpdateNoteCommandReturnsFailureWhenNoRowAffected(): void
    {
        $gateway = $this->createStub(TableGateway::class);
        $gateway->method('updateWith')->willReturn(0);

        $handler = new UpdateNoteHandler($gateway);
        $result  = $handler->updateNoteCommand(new UpdateNoteCommand('999', 'renamed'));

        self::assertSame(MessageStatus::Failure, $result->getStatus());
    }

    public function testUpdateNoteCommandReturnsSuccessWhenRowAffected(): void
    {
        $gateway = $this->createStub(TableGateway::class);
        $gateway->method('updateWith')->willReturn(1);

        $handler = new UpdateNoteHandler($gateway);
        $result  = $handler->updateNoteCommand(new UpdateNoteCommand('1', 'renamed'));

        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(MessageStatus::Success, $result->getStatus());
        self::assertSame(['id' => '1', 'title' => 'renamed'], $result->getResult());
    }
}
