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
use Realblog\Infra\DB;
use Realblog\Infra\Finder;
use Realblog\Infra\View;
use ApprovalTests\Approvals;
use Realblog\Infra\Request;
use XH\CSRFProtection as CsrfProtector;

class DataExchangeControllerTest extends TestCase
{
    public function testSuccessfulExportRedirects(): void
    {
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['realblog'];
        $db = $this->createStub(DB::class);
        $db->method('exportToCsv')->willReturn(true);
        $finder = $this->createStub(Finder::class);
        $csrfProtector = $this->createStub(CsrfProtector::class);
        $view = new View("./views/", $lang);
        $sut = new DataExchangeController($db, $finder, $csrfProtector, $view);
        $request = $this->createStub(Request::class);
        $request->method("contentFolder")->willReturn("./content/");
        $response = $sut($request, "export_to_csv");
        $this->assertEquals("http://example.com/?&realblog&admin=data_exchange", $response->location());
    }

    public function testExportReportsFailure(): void
    {
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['realblog'];
        $db = $this->createStub(DB::class);
        $db->method('exportToCsv')->willReturn(false);
        $finder = $this->createStub(Finder::class);
        $csrfProtector = $this->createStub(CsrfProtector::class);
        $view = new View("./views/", $lang);
        $sut = new DataExchangeController($db, $finder, $csrfProtector, $view);
        $request = $this->createStub(Request::class);
        $request->method("contentFolder")->willReturn("./content/");
        $response = $sut($request, "export_to_csv");
        Approvals::verifyHtml($response->output());
    }

    public function testSuccessfulImportRedirects(): void
    {
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['realblog'];
        $db = $this->createStub(DB::class);
        $db->method('importFromCsv')->willReturn(true);
        $finder = $this->createStub(Finder::class);
        $csrfProtector = $this->createStub(CsrfProtector::class);
        $view = new View("./views/", $lang);
        $sut = new DataExchangeController($db, $finder, $csrfProtector, $view);
        $request = $this->createStub(Request::class);
        $request->method("contentFolder")->willReturn("./content/");
        $response = $sut($request, "import_from_csv");
        $this->assertEquals("http://example.com/?&realblog&admin=data_exchange", $response->location());
    }

    public function testImportReportsFailure(): void
    {
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['realblog'];
        $db = $this->createStub(DB::class);
        $db->method('importFromCsv')->willReturn(false);
        $finder = $this->createStub(Finder::class);
        $csrfProtector = $this->createStub(CsrfProtector::class);
        $view = new View("./views/", $lang);
        $sut = new DataExchangeController($db, $finder, $csrfProtector, $view);
        $request = $this->createStub(Request::class);
        $request->method("contentFolder")->willReturn("./content/");
        $response = $sut($request, "import_from_csv");
        Approvals::verifyHtml($response->output());
    }
}
