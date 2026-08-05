<?php

declare(strict_types=1);

namespace App\CommandHandler;

use App\Command\CreateNoteCommand;
use App\Command\NoteCommandResult;
use App\Event\NoteCreatedEvent;
use Override;
use PhpDb\Sql\Insert;
use PhpDb\TableGateway\TableGateway;
use Psl\Type;
use RuntimeException;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\ResultInterface;

final readonly class CreateNoteHandler implements CommandHandlerInterface
{
    private const string TABLE = 'notes';

    public function __construct(
        private TableGateway $notes,
    ) {}

    #[Override]
    public function handle(MessageInterface $message): ResultInterface
    {
        $message = Type\instance_of(CreateNoteCommand::class)->assert($message);

        $insert = new Insert(self::TABLE);
        $insert->values(['title' => $message->title]);

        $this->notes->insertWith($insert);
        $id = $this->notes->getLastInsertValue();

        if ($id === false || $id === null) {
            throw new RuntimeException('Failed to determine the generated id for the new note.');
        }

        $result = new NoteCommandResult($message, MessageStatus::Success, ['id' => $id, 'title' => $message->title]);
        $result->setEvent(new NoteCreatedEvent($id, $message->title));

        return $result;
    }
}
