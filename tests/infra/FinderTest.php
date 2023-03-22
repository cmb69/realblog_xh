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
use Realblog\Value\Article;
use Realblog\Value\FullArticle;
use Realblog\Value\MostPopularArticle;

class FinderTest extends TestCase
{
    /** @var Finder */
    private $sut;

    /** @var DB */
    private $db;

    public function setUp(): void
    {
        $this->db = new DB(":memory:");
        $this->sut = new Finder($this->db);
    }

    public function testFindsInsertedArticle(): void
    {
        $this->assertEquals(1, $this->db->insertArticle($this->article()));
        $actual = $this->sut->findById(1);
        $this->assertEquals($this->article(), $actual);
    }

    public function testDoesNotFindDeletedArticle(): void
    {
        $this->assertEquals(1, $this->db->insertArticle($this->article()));
        $this->assertEquals(1, $this->db->deleteArticle($this->article()));
        $this->assertNull($this->sut->findById(1));
    }

    public function testFindsAllArticles(): void
    {
        $this->assertEquals(1, $this->db->insertArticle($this->article()));
        $this->assertEquals(1, $this->db->insertArticle($this->article()));
        $this->assertEquals(1, $this->db->insertArticle($this->article()));
        $articles = $this->sut->findArticles(1, 10, 0);
        $this->assertCount(3, $articles);
        $this->assertContainsOnlyInstancesOf(Article::class, $articles);
    }

    public function testFindsArchivedArticlesInGivenPeriod(): void
    {
        $start = strtotime("2022-01-01T12:00:00");
        $end = strtotime("2022-12-31T12:00:00");
        $article = $this->article(["date" => $start - 1, "status" => 2]);
        $this->assertEquals(1, $this->db->insertArticle($article));
        $article = $this->article(["date" => $start, "status" => 2]);
        $this->assertEquals(1, $this->db->insertArticle($article));
        $article = $this->article(["date" => $end - 1, "status" => 2]);
        $this->assertEquals(1, $this->db->insertArticle($article));
        $article = $this->article(["date" => $end, "status" => 2]);
        $this->assertEquals(1, $this->db->insertArticle($article));
        $articles = $this->sut->findArchivedArticlesInPeriod($start, $end);
        $this->assertCount(2, $articles);
        $this->assertContainsOnlyInstancesOf(Article::class, $articles);
        $this->assertEquals(3, $articles[0]->id);
        $this->assertEquals(2, $articles[1]->id);
    }

    public function testFindsAllArchiveYears(): void
    {
        $article = $this->article(["date" => strtotime("2021-12-31"), "status" => 2]);
        $this->assertEquals(1, $this->db->insertArticle($article));
        $article = $this->article(["date" => strtotime("2022-01-01"), "status" => 2]);
        $this->assertEquals(1, $this->db->insertArticle($article));
        $articles = $this->sut->findArchiveYears();
        $this->assertCount(2, $articles);
        $this->assertContainsOnly("int", $articles);
    }

    public function testFindsAllArchivedArticlesForGivenSearchTerm(): void
    {
        $article = $this->article(["status" => 2, "title" => "foo", "body" => "bar"]);
        $this->assertEquals(1, $this->db->insertArticle($article));
        $article = $this->article(["status" => 2, "title" => "foo search bar"]);
        $this->assertEquals(1, $this->db->insertArticle($article));
        $article = $this->article(["status" => 2, "body" => "foo search bar"]);
        $this->assertEquals(1, $this->db->insertArticle($article));
        $articles = $this->sut->findArchivedArticlesContaining("search");
        $this->assertCount(2, $articles);
        $this->assertContainsOnlyInstancesOf(Article::class, $articles);
    }

    public function testCountsArticlesWithStatus(): void
    {
        $article = $this->article(["status" => 0]);
        $this->assertEquals(1, $this->db->insertArticle($article));
        $article = $this->article(["status" => 1]);
        $this->assertEquals(1, $this->db->insertArticle($article));
        $article = $this->article(["status" => 2]);
        $this->assertEquals(1, $this->db->insertArticle($article));
        $this->assertEquals(1, $this->sut->countArticlesWithStatus(Article::MASK_PUBLISHED));
    }

    public function testFindsArticlesWithStatus(): void
    {
        $article = $this->article(["status" => 0]);
        $this->assertEquals(1, $this->db->insertArticle($article));
        $article = $this->article(["status" => 1]);
        $this->assertEquals(1, $this->db->insertArticle($article));
        $article = $this->article(["status" => 2]);
        $this->assertEquals(1, $this->db->insertArticle($article));
        $articles = $this->sut->findArticlesWithStatus(Article::MASK_PUBLISHED, 100, 0);
        $this->assertCount(1, $articles);
        $this->assertContainsOnlyInstancesOf(Article::class, $articles);
    }

    public function testFindsFeedableArticles(): void
    {
        $article = $this->article(["feedable" => true]);
        $this->assertEquals(1, $this->db->insertArticle($article));
        $article = $this->article(["feedable" => false]);
        $this->assertEquals(1, $this->db->insertArticle($article));
        $articles = $this->sut->findFeedableArticles(100);
        $this->assertCount(1, $articles);
        $this->assertContainsOnlyInstancesOf(Article::class, $articles);
    }

    public function testFindsMostPopularArticles(): void
    {
        $this->assertEquals(1, $this->db->insertArticle($this->article()));
        $this->assertEquals(1, $this->db->insertArticle($this->article()));
        $this->assertEquals(1, $this->db->insertArticle($this->article()));
        for ($i = 0; $i < 5; $i++) {
            $this->db->recordPageView(1);
        }
        for ($i = 0; $i < 10; $i++) {
            $this->db->recordPageView(3);
        }
        $articles = $this->sut->findMostPopularArticles(2);
        $this->assertcount(2, $articles);
        $this->containsOnlyInstancesOf(MostPopularArticle::class, $articles);
        $this->assertEquals(3, $articles[0]->id);
        $this->assertEquals(1, $articles[1]->id);
    }

    public function testFindsAllUniqueCategories(): void
    {
        $article = $this->article(["categories" => ",foo,baz,"]);
        $this->assertEquals(1, $this->db->insertArticle($article));
        $article = $this->article(["categories" => ",bar,foo,"]);
        $this->assertEquals(1, $this->db->insertArticle($article));
        $categories = $this->sut->findAllCategories();
        $this->assertEquals(["bar", "baz", "foo"], $categories);
    }

    private function article(array $options = []): FullArticle
    {
        return new FullArticle(
            1,
            1,
            $options["date"] ?? 1676974220,
            1676974220,
            1676974220,
            $options["status"] ?? 1,
            $options["categories"] ?? ",,",
            $options["title"] ?? "My Article",
            "You should read it!",
            $options["body"] ?? "It has a lot of useful info.",
            $options["feedable"] ?? false,
            false
        );
    }
}
