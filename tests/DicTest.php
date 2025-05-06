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

namespace Realblog;

use PHPUnit\Framework\TestCase;

class DicTest extends TestCase
{
    public function setUp(): void
    {
        global $pth, $plugin_cf, $plugin_tx;

        $pth = ["folder" => ["content" => "", "images" => "", "plugin" => "", "plugins" => ""]];
        $plugin_cf = ["realblog" => []];
        $plugin_tx = ["realblog" => ["rss_page" => ""]];
    }

    public function testMakesGeneralController(): void
    {
        $this->assertInstanceOf(GeneralController::class, Dic::makeGeneralController());
    }

    public function testMakesBlogController(): void
    {
        $this->assertInstanceOf(BlogController::class, Dic::makeBlogController());
    }

    public function testMakesArticleController(): void
    {
        $this->assertInstanceOf(ArticleController::class, Dic::articleController());
    }

    public function testMakesLinkController(): void
    {
        $this->assertInstanceOf(LinkController::class, Dic::makeLinkController());
    }

    public function testMakesFeedLinkController(): void
    {
        $this->assertInstanceOf(FeedLinkController::class, Dic::makeFeedLinkController());
    }

    public function testMakesMostPopularController(): void
    {
        $this->assertInstanceOf(MostPopularController::class, Dic::makeMostPopularController());
    }

    public function testMakesFeedController(): void
    {
        $this->assertInstanceOf(FeedController::class, Dic::makeFeedController());
    }

    public function testMakesInfoController(): void
    {
        $this->assertInstanceOf(InfoController::class, Dic::makeInfoController());
    }

    public function testMakesMainAdminController(): void
    {
        $this->assertInstanceOf(MainAdminController::class, Dic::makeMainAdminController());
    }

    public function testMakesDataExchangeController(): void
    {
        $this->assertInstanceOf(DataExchangeController::class, Dic::makeDataExchangeController());
    }
}
