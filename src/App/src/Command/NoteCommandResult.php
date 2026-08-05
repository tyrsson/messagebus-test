<?php

declare(strict_types=1);

namespace App\Command;

use Override;
use Webware\MessageBus\Command\CommandInterface;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\Event\EventAwareInterface;
use Webware\MessageBus\Event\EventAwareTrait;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\StatusInterface;

// Webware\MessageBus\Command\CommandResult is final readonly and cannot be
// extended as the messagebus-event docs suggest - see docs/failure-notes.md #1.
final class NoteCommandResult implements CommandResultInterface, EventAwareInterface
{
    use EventAwareTrait;

    public function __construct(
        private readonly CommandInterface $command,
        private readonly MessageStatus $status,
        private readonly mixed $result,
    ) {}

    #[Override]
    public function getCommand(): CommandInterface
    {
        return $this->command;
    }

    #[Override]
    public function getResult(): mixed
    {
        return $this->result;
    }

    #[Override]
    public function getStatus(): StatusInterface
    {
        return $this->status;
    }
}
