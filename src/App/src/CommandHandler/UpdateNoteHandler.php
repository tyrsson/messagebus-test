<?php

declare(strict_types=1);

namespace App\CommandHandler;

use App\Command\UpdateNoteCommand;
use PhpDb\Sql\Update;
use PhpDb\TableGateway\TableGateway;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageStatus;

final readonly class UpdateNoteHandler implements CommandHandlerInterface
{
    private const string TABLE = 'notes';

    public function __construct(
        private TableGateway $notes,
    ) {}

    public function updateNoteCommand(UpdateNoteCommand $command): CommandResult
    {
        $update = new Update(self::TABLE);
        $update->set(['title' => $command->title]);
        $update->where(['id' => $command->id]);

        $affected = $this->notes->updateWith($update);
        $status   = $affected > 0 ? MessageStatus::Success : MessageStatus::Failure;

        return new CommandResult($command, $status, ['id' => $command->id, 'title' => $command->title]);
    }
}
