<?php

declare(strict_types=1);

namespace App\Ddl;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Ddl\Column;
use PhpDb\Sql\Ddl\Constraint;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Sql;

final class NotesTable
{
    private const string TABLE = 'notes';

    public static function createIfNotExists(AdapterInterface $adapter): void
    {
        $table = new CreateTable(self::TABLE);
        $table->ifNotExists(true);

        $id = new Column\Integer('id');
        $id->setOption('AUTO_INCREMENT', true);
        $id->addConstraint(new Constraint\PrimaryKey());
        $table->addColumn($id);

        $table->addColumn(new Column\Varchar('title', 255));

        // A plain string default is bound/quoted as a string literal rather than
        // rendered as the raw SQL keyword - see docs/failure-notes.md #7. Literal
        // must be used to get an unquoted `DEFAULT CURRENT_TIMESTAMP`.
        $table->addColumn(new Column\Timestamp('created_at', false, new Literal('CURRENT_TIMESTAMP')));

        $sql = new Sql($adapter);
        $adapter->query($sql->buildSqlString($table), AdapterInterface::QUERY_MODE_EXECUTE);
    }
}
