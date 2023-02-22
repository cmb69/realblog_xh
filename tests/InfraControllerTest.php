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
use Realblog\Infra\Request;
use Realblog\Infra\SystemChecker;
use Realblog\Infra\View;

class InfroControllerTest extends TestCase
{
    public function testShowsPluginInfo(): void
    {
        global $pth;

        $pth = ["folder" => ["plugins" => "./plugins/"]];
        $conf = XH_includeVar("./config/config.php", "plugin_cf")["realblog"];
        $plugin_tx = XH_includeVar("./languages/en.php", "plugin_tx");
        $text = $plugin_tx["realblog"];
        $systemChecker = $this->createStub(SystemChecker::class);
        $systemChecker->method("checkPHPVersion")->willReturn(false);
        $systemChecker->method("checkExtension")->willReturn(false);
        $systemChecker->method("checkXHVersion")->willReturn(false);
        $systemChecker->method("checkWritability")->willReturn(false);
        $view = new View("./views/", $text);
        $sut = new InfoController($conf, $text, $systemChecker, $view);
        $response = $sut(new Request);
        Approvals::verifyHtml($response->output());
    }
}
