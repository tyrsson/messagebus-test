<?php

declare(strict_types=1);

namespace App\CommandHandler;

use App\Command\NoteCommandResult;
use App\Command\UpdateNoteCommand;
use Override;
use PhpDb\Sql\Update;
use PhpDb\TableGateway\TableGateway;
use Psl\Type;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\ResultInterface;

final readonly class UpdateNoteHandler implements CommandHandlerInterface
{
    private const string TABLE = 'notes';

    public function __construct(
        private TableGateway $notes,
    ) {}

    #[Override]
    public function handle(MessageInterface $message): ResultInterface
    {
        $message = Type\instance_of(UpdateNoteCommand::class)->assert($message);

        $update = new Update(self::TABLE);
        $update->set(['title' => $message->title]);
        $update->where(['id' => $message->id]);

        $affected = $this->notes->updateWith($update);
        $status   = $affected > 0 ? MessageStatus::Success : MessageStatus::Failure;

        return new NoteCommandResult($message, $status, ['id' => $message->id, 'title' => $message->title]);
    }
}
