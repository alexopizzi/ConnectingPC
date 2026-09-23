<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Database\SqlSplitter;
use PHPUnit\Framework\TestCase;

final class SqlSplitterTest extends TestCase
{
    public function testSplitsIgnoringSemicolonsInStringsAndComments(): void
    {
        $sql = <<<'SQL'
            -- commento; con punto e virgola
            CREATE TABLE a (id INT); # altro commento;
            INSERT INTO a VALUES ('x;y'), ('it''s; ok'), ("a\";b");
            /* blocco; commento */ UPDATE `a;b` SET id = 1;
            SQL;

        $statements = SqlSplitter::split($sql);

        self::assertCount(3, $statements);
        self::assertSame('CREATE TABLE a (id INT)', $statements[0]);
        self::assertStringContainsString("('x;y'), ('it''s; ok'), (\"a\\\";b\")", $statements[1]);
        self::assertStringEndsWith('UPDATE `a;b` SET id = 1', $statements[2]);
    }

    public function testIgnoresEmptyStatements(): void
    {
        self::assertSame(['SELECT 1'], SqlSplitter::split(";;\n SELECT 1 ;\n\n"));
    }
}
