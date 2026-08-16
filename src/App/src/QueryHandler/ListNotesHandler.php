<?php

declare(strict_types=1);

namespace App\QueryHandler;

use App\Query\ListNotesQuery;
use PhpDb\Sql\Select;
use PhpDb\TableGateway\TableGateway;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\MessageBus\QueryHandlerInterface;

final readonly class ListNotesHandler implements QueryHandlerInterface
{
    private const string TABLE = 'notes';

    public function __construct(
        private TableGateway $notes,
    ) {}

    public function listNotesQuery(ListNotesQuery $query): QueryResult
    {
        $select = new Select(self::TABLE);
        $select->order('created_at DESC');
        $select->limit($query->limit);

        $rows = $this->notes->selectWith($select)->toArray();

        return new QueryResult($query, MessageStatus::Success, $rows);
    }
}
