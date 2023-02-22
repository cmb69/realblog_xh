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
use Realblog\Infra\Url;
use Realblog\Infra\View;

class FeedLinkControllerTest extends TestCase
{
    public function testDefaultActionRendersFeedLink(): void
    {
        $text = XH_includeVar("./languages/en.php", 'plugin_tx')['realblog'];
        $sut = new FeedLinkController(new View("./views/", $text));
        $request = $this->createStub(Request::class);
        $request->method("url")->willReturn((new Url)->withPage(""));
        $request->method("pluginsFolder")->willReturn("./plugins/");
        $response = $sut($request, "_self");
        Approvals::verifyHtml($response);
    }
}
