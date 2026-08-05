<?php

declare(strict_types=1);

namespace App\QueryHandler;

use App\Query\ListNotesQuery;
use Override;
use PhpDb\Sql\Select;
use PhpDb\TableGateway\TableGateway;
use Psl\Type;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\MessageBus\QueryHandlerInterface;
use Webware\MessageBus\ResultInterface;

final readonly class ListNotesHandler implements QueryHandlerInterface
{
    private const string TABLE = 'notes';

    public function __construct(
        private TableGateway $notes,
    ) {}

    #[Override]
    public function handle(MessageInterface $message): ResultInterface
    {
        $message = Type\instance_of(ListNotesQuery::class)->assert($message);

        $select = new Select(self::TABLE);
        $select->order('created_at DESC');
        $select->limit($message->limit);

        $rows = $this->notes->selectWith($select)->toArray();

        return new QueryResult($message, MessageStatus::Success, $rows);
    }
}
