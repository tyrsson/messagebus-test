<?php

declare(strict_types=1);

namespace App\CommandHandler;

use App\Command\DeleteNoteCommand;
use App\Command\NoteCommandResult;
use Override;
use PhpDb\Sql\Delete;
use PhpDb\TableGateway\TableGateway;
use Psl\Type;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\ResultInterface;

final readonly class DeleteNoteHandler implements CommandHandlerInterface
{
    private const string TABLE = 'notes';

    public function __construct(
        private TableGateway $notes,
    ) {}

    #[Override]
    public function handle(MessageInterface $message): ResultInterface
    {
        $message = Type\instance_of(DeleteNoteCommand::class)->assert($message);

        $delete = new Delete(self::TABLE);
        $delete->where(['id' => $message->id]);

        $affected = $this->notes->deleteWith($delete);
        $status   = $affected > 0 ? MessageStatus::Success : MessageStatus::Failure;

        return new NoteCommandResult($message, $status, ['id' => $message->id]);
    }
}
