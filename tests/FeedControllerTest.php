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
use Realblog\Infra\Finder;
use Realblog\Infra\ScriptEvaluator;
use ApprovalTests\Approvals;

class FeedControllerTest extends TestCase
{
    public function testDefaultActionRendersFeedWithNoArticles(): void
    {
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['realblog'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['realblog'];
        $finder = $this->createStub(Finder::class);
        $finder->method('findFeedableArticles')->willReturn([]);
        $scriptEvaluator = $this->createStub(ScriptEvaluator::class);
        $sut = new FeedController("./", "../../userfiles/images/", $conf, $lang, "/", $finder, $scriptEvaluator);
        $response = $sut();
        Approvals::verifyHtml($response);
    }
}
