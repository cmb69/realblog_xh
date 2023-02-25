<?php

/**
 * Copyright 2023 Christoph M. Becker
 *
 * This file is part of Realblog_XH.
 *
 * Realblog_XH is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Realblog_XH is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Realblog_XH.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Realblog\Infra;

use ApprovalTests\Approvals;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Realblog\Value\Article;
use Realblog\Value\FullArticle;

class DbTest extends TestCase
{
    /** @var DB */
    private $sut;

    public function setUp(): void
    {
        $this->sut = new DB(":memory:");
    }

    public function testImportsFlatfile(): void
    {
        $this->sut = null;
        chdir(__DIR__);
        $sut = new DB(":memory:");
        vfsStream::setup('root');
        mkdir(vfsStream::url('root/realblog/'));
        $filename = vfsStream::url('root/realblog/realblog.csv');
        $sut->exportToCsv($filename);
        Approvals::verifyStringWithFileExtension(file_get_contents($filename), "csv");
    }

    public function testCanInsertArticle(): void
    {
        $this->assertEquals(1, $this->sut->insertArticle($this->article()));
    }

    public function testCanUpdateArticle(): void
    {
        $this->assertEquals(1, $this->sut->insertArticle($this->article()));
        $this->assertEquals(1, $this->sut->updateArticle($this->article()));
    }

    public function testCanDeleteArticle(): void
    {
        $this->assertEquals(1, $this->sut->insertArticle($this->article()));
        $this->assertEquals(1, $this->sut->deleteArticle($this->article()));
    }

    public function testAutoChangesStatus(): void
    {
        $this->assertEquals(1, $this->sut->insertArticle($this->article()));
        $result = $this->sut->getConnection()->querySingle("SELECT status FROM articles WHERE id = 1");
        $this->assertEquals(Article::PUBLISHED, $result);
        $this->sut->autoChangeStatus("archiving_date", Article::ARCHIVED);
        $result = $this->sut->getConnection()->querySingle("SELECT status FROM articles WHERE id = 1");
        $this->assertEquals(Article::ARCHIVED, $result);
    }

    public function testUpdatesStatuses(): void
    {
        $this->assertEquals(1, $this->sut->insertArticle($this->article()));
        $this->assertEquals(1, $this->sut->insertArticle($this->article()));
        $this->assertEquals(1, $this->sut->insertArticle($this->article()));
        $this->assertEquals(2, $this->sut->updateStatusOfArticlesWithIds([1, 3], 2));
    }

    public function testDeletesArticles(): void
    {
        $this->assertEquals(1, $this->sut->insertArticle($this->article()));
        $this->assertEquals(1, $this->sut->insertArticle($this->article()));
        $this->assertEquals(1, $this->sut->insertArticle($this->article()));
        $this->assertEquals(2, $this->sut->deleteArticlesWithIds([1, 3]));
    }

    public function testExportsToCsv(): void
    {
        vfsStream::setup('root');
        $this->assertEquals(1, $this->sut->insertArticle($this->article()));
        $this->assertEquals(1, $this->sut->insertArticle($this->article()));
        $this->assertEquals(1, $this->sut->insertArticle($this->article()));
        $filename = vfsStream::url('root/realblog.csv');
        $result = $this->sut->exportToCsv($filename);
        $this->assertTrue($result);
        Approvals::verifyStringWithFileExtension(file_get_contents($filename), "csv");
    }

    public function testDoesNotExportToCsvIfFileNotReadable(): void
    {
        vfsStream::setup('root');
        $this->assertEquals(1, $this->sut->insertArticle($this->article()));
        $this->assertEquals(1, $this->sut->insertArticle($this->article()));
        $this->assertEquals(1, $this->sut->insertArticle($this->article()));
        $filename = vfsStream::url('root/realblog.csv');
        touch($filename);
        chmod($filename, 0444);
        $result = $this->sut->exportToCsv($filename);
        $this->assertFalse($result);
    }

    public function testImportsFromCsv(): void
    {
        vfsStream::setup('root');
        $filename = vfsStream::url('root/realblog.csv');
        $approval = __DIR__ . "/approvals/DBTest.testExportsToCsv.approved.csv";
        copy($approval, $filename);
        $result = $this->sut->importFromCsv($filename);
        $this->assertTrue($result);
        $result = $this->sut->exportToCsv($filename);
        $this->assertFileEquals($approval, $filename);
    }

    private function article(): FullArticle
    {
        return new FullArticle(
            1,
            1,
            1676974220,
            1676974220,
            1676974220,
            1,
            ",,",
            "My Article",
            "You should read it!",
            "It has a lot of useful info.",
            false,
            false
        );
    }
}
