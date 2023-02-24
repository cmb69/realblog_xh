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

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;
use Realblog\Infra\DB;
use Realblog\Infra\FakeRequest;
use Realblog\Value\Article;

class GeneralControllerTest extends TestCase
{
    public function testAutoPublishesWhenConfigured()
    {
        $db = $this->db();
        $db->expects($this->once())->method("autoChangeStatus")->with('publishing_date', Article::PUBLISHED);
        $sut = new GeneralController($this->conf(["auto_publish" => "true"]), $db);
        $sut(new FakeRequest());
    }

    public function testAutoArchivesWhenConfigured()
    {
        $db = $this->db();
        $db->expects($this->once())->method("autoChangeStatus")->with('archiving_date', Article::ARCHIVED);
        $sut = new GeneralController($this->conf(["auto_archive" => "true"]), $db);
        $sut(new FakeRequest());
    }

    public function testRendersFeedLinkWhenConfigured()
    {
        $sut = new GeneralController($this->conf(["rss_enabled" => "true"]), $this->db());
        $response = $sut(new FakeRequest());
        Approvals::verifyHtml($response->hjs());
    }

    private function conf($options = [])
    {
        $conf = XH_includeVar("./config/config.php", "plugin_cf")["realblog"];
        $conf['auto_archive'] = "";
        $conf["auto_publish"] = "";
        $conf['rss_enabled'] = "";
        return $options + $conf;
    }

    private function db()
    {
        return $this->createMock(DB::class);
    }
}
