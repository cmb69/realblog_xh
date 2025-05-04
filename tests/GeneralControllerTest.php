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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Plib\FakeRequest;
use Plib\View;
use Realblog\Infra\DB;
use Realblog\Value\Article;

class GeneralControllerTest extends TestCase
{
    /** @var array<string,string> */
    private $conf;

    /** @var DB&MockObject */
    private $db;

    /** @var View */
    private $view;

    public function setUp(): void
    {
        $this->conf = XH_includeVar("./config/config.php", "plugin_cf")["realblog"];
        $this->conf['auto_archive'] = "";
        $this->conf["auto_publish"] = "";
        $this->conf['rss_enabled'] = "";
        $this->db = $this->createMock(DB::class);;
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["realblog"]);
    }

    private function sut(): GeneralController
    {
        return new GeneralController($this->conf, $this->db, $this->view);
    }

    public function testAutoPublishesWhenConfigured()
    {
        $this->conf["auto_publish"] = "true";
        $this->db->expects($this->once())->method("autoChangeStatus")->with('publishing_date', Article::PUBLISHED);
        $this->sut()(new FakeRequest());
    }

    public function testAutoArchivesWhenConfigured()
    {
        $this->conf["auto_archive"] = "true";
        $this->db->expects($this->once())->method("autoChangeStatus")->with('archiving_date', Article::ARCHIVED);
        $this->sut()(new FakeRequest());
    }

    public function testRendersFeedLinkWhenConfigured()
    {
        $this->conf["rss_enabled"] = "true";
        $response = $this->sut()(new FakeRequest());
        $this->assertSame(
            "\n<link rel=\"alternate\" type=\"application/rss+xml\" href=\"./?function=realblog_feed\">\n",
            $response->hjs()
        );
    }
}
