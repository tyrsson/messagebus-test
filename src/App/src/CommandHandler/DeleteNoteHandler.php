<?php

declare(strict_types=1);

namespace App\CommandHandler;

use App\Command\DeleteNoteCommand;
use PhpDb\Sql\Delete;
use PhpDb\TableGateway\TableGateway;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageStatus;

final readonly class DeleteNoteHandler implements CommandHandlerInterface
{
    private const string TABLE = 'notes';

    public function __construct(
        private TableGateway $notes,
    ) {}

    public function deleteNoteCommand(DeleteNoteCommand $command): CommandResult
    {
        $delete = new Delete(self::TABLE);
        $delete->where(['id' => $command->id]);

        $affected = $this->notes->deleteWith($delete);
        $status   = $affected > 0 ? MessageStatus::Success : MessageStatus::Failure;

        return new CommandResult($command, $status, ['id' => $command->id]);
    }
}
