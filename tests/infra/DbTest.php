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

use PHPUnit\Framework\TestCase;
use Realblog\Value\FullArticle;

class DbTest extends TestCase
{
    /** @var DB */
    private $sut;

    public function setUp(): void
    {
        $this->sut = new DB(":memory:");
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
