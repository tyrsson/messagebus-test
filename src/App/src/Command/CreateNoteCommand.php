<?php

declare(strict_types=1);

namespace App\Command;

use Webware\MessageBus\Command\CommandInterface;

final readonly class CreateNoteCommand implements CommandInterface
{
    public function __construct(
        public string $title,
    ) {}
}
