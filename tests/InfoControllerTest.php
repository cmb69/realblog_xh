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
use Plib\FakeRequest;
use Plib\FakeSystemChecker;
use Plib\View;

class InfoControllerTest extends TestCase
{
    /** @var array<string,string> */
    private $conf;

    /** @var View */
    private $view;

    public function setUp(): void
    {
        $this->conf = XH_includeVar("./config/config.php", "plugin_cf")["realblog"];;
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["realblog"]);
    }

    private function sut(): InfoController
    {
        return new InfoController("./plugins/realblog/", $this->conf, new FakeSystemChecker(), $this->view);;
    }

    public function testShowsPluginInfo(): void
    {
        $request = new FakeRequest();
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
    }
}
