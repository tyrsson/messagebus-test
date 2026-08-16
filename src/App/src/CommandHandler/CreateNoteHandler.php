<?php

declare(strict_types=1);

namespace App\CommandHandler;

use App\Command\CreateNoteCommand;
use App\Event\NoteCreatedEvent;
use PhpDb\Sql\Insert;
use PhpDb\TableGateway\TableGateway;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageStatus;

final readonly class CreateNoteHandler implements CommandHandlerInterface
{
    private const string TABLE = 'notes';

    public function __construct(
        private TableGateway $notes,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    /** @throws RuntimeException when the generated id cannot be determined. */
    public function createNoteCommand(CreateNoteCommand $command): CommandResult
    {
        $insert = new Insert(self::TABLE);
        $insert->values(['title' => $command->title, 'body' => $command->body]);

        $this->notes->insertWith($insert);
        $id = $this->notes->getLastInsertValue();

        if ($id === false || $id === null) {
            throw new RuntimeException('Failed to determine the generated id for the new note.');
        }

        $this->eventDispatcher->dispatch(new NoteCreatedEvent($id, $command->title));

        return new CommandResult($command, MessageStatus::Success, ['id' => $id, 'title' => $command->title]);
    }
}
