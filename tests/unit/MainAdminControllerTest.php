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
use PHPUnit\Framework\MockObject;
use ApprovalTests\Approvals;

use XH\CSRFProtection as CsrfProtector;

class MainAdminControllerTest extends TestCase
{
    /** @var MainAdminController */
    private $sut;

    /** @var Finder&MockObject */
    private $finder;

    public function setUp(): void
    {
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['realblog'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['realblog'];
        $db = $this->createStub(DB::class);
        $this->finder = $this->createStub(Finder::class);
        $csrfProtector = $this->createStub(CsrfProtector::class);
        $view = new View("./views/", $lang);
        $editor = $this->createStub(Editor::class);
        $this->sut = new MainAdminController(
            "./",
            $conf,
            $lang,
            "/",
            "en",
            $db,
            $this->finder,
            $csrfProtector,
            $view,
            $editor,
            1675205155
        );
    }

    public function testDefaultActionRendersOverview(): void
    {
        $this->finder->method('findArticlesWithStatus')->willReturn([]);
        $response = $this->sut->defaultAction();
        Approvals::verifyHtml($response->output());
    }

    public function testCreateActionRendersArticle(): void
    {
        $response = $this->sut->createAction();
        Approvals::verifyHtml($response->output());
        $this->assertEquals("Create new article", $response->title());
    }

    public function testCreateActionOutputsHjs(): void
    {
        $response = $this->sut->createAction();
        Approvals::verifyHtml($response->hjs());
    }

    public function testCreateActionOutputsBjs(): void
    {
        $this->finder->method('findAllCategories')->willReturn(["cat1", "cat2"]);
        $response = $this->sut->createAction();
        Approvals::verifyHtml($response->bjs());
    }
}