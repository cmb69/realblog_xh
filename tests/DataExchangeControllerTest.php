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
use Realblog\Infra\FakeCsrfProtector;
use Realblog\Infra\FakeFileSystem;
use Realblog\Infra\FakeRequest;
use XH\CSRFProtection as CsrfProtector;

class DataExchangeControllerTest extends TestCase
{
    public function testRendersOverview()
    {
        $sut = new DataExchangeController(
            $this->db(true),
            $this->finder(),
            new FakeCsrfProtector,
            new FakeFileSystem(),
            $this->view()
        );
        $request = new FakeRequest(["path" => ["folder" => ["content" => "./content/"]]]);
        $response = $sut($request, "");
        Approvals::verifyHtml($response->output());
    }

    public function testRendersOverviewWithCsvFile()
    {
        $sut = new DataExchangeController(
            $this->db(true),
            $this->finder(),
            new FakeCsrfProtector,
            new FakeFileSystem(["isReadable" => true, "fileMTime" => 1677251242]),
            $this->view()
        );
        $request = new FakeRequest(["path" => ["folder" => ["content" => "./content/"]]]);
        $response = $sut($request, "");
        Approvals::verifyHtml($response->output());
    }

    public function testExportIsCsrfProtected()
    {
        $sut = new DataExchangeController(
            $this->db(true),
            $this->finder(),
            $csrfProtector = new FakeCsrfProtector,
            new FakeFileSystem(),
            $this->view()
        );
        $request = new FakeRequest(["path" => ["folder" => ["content" => "./content/"]]]);
        $sut($request, "export_to_csv");
        $this->assertTrue($csrfProtector->hasChecked());
    }

    public function testSuccessfulExportRedirects()
    {
        $sut = new DataExchangeController(
            $this->db(true),
            $this->finder(),
            new FakeCsrfProtector,
            new FakeFileSystem(),
            $this->view()
        );
        $request = new FakeRequest(["path" => ["folder" => ["content" => "./content/"]]]);
        $response = $sut($request, "export_to_csv");
        $this->assertEquals("http://example.com/?realblog&admin=data_exchange", $response->location());
    }

    public function testExportReportsFailure()
    {
        $sut = new DataExchangeController(
            $this->db(false),
            $this->finder(),
            new FakeCsrfProtector,
            new FakeFileSystem(),
            $this->view()
        );
        $request = new FakeRequest(["path" => ["folder" => ["content" => "./content/"]]]);
        $response = $sut($request, "export_to_csv");
        Approvals::verifyHtml($response->output());
    }

    public function testImportIsCsrfProtected()
    {
        $sut = new DataExchangeController(
            $this->db(true),
            $this->finder(),
            $csrfProtector = new FakeCsrfProtector,
            new FakeFileSystem(),
            $this->view()
        );
        $request = new FakeRequest(["path" => ["folder" => ["content" => "./content/"]]]);
        $sut($request, "import_from_csv");
        $this->assertTrue($csrfProtector->hasChecked());
    }

    public function testSuccessfulImportRedirects()
    {
        $sut = new DataExchangeController(
            $this->db(true),
            $this->finder(),
            new FakeCsrfProtector,
            new FakeFileSystem(),
            $this->view()
        );
        $request = new FakeRequest(["path" => ["folder" => ["content" => "./content/"]]]);
        $response = $sut($request, "import_from_csv");
        $this->assertEquals("http://example.com/?realblog&admin=data_exchange", $response->location());
    }

    public function testImportReportsFailure()
    {
        $sut = new DataExchangeController(
            $this->db(false),
            $this->finder(),
            new FakeCsrfProtector,
            new FakeFileSystem(),
            $this->view()
        );
        $request = new FakeRequest(["path" => ["folder" => ["content" => "./content/"]]]);
        $response = $sut($request, "import_from_csv");
        Approvals::verifyHtml($response->output());
    }

    private function db($success)
    {
        $db = $this->createStub(DB::class);
        $db->method('exportToCsv')->willReturn($success);
        $db->method('importFromCsv')->willReturn($success);
        return $db;
    }

    private function finder()
    {
        $finder = $this->createStub(Finder::class);
        $finder->method("countArticlesWithStatus")->willReturn(3);
        return $finder;
    }

    private function csrfProtector()
    {
        return $this->createStub(CsrfProtector::class);
    }

    private function view()
    {
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $text = $plugin_tx['realblog'];
        return new View("./views/", $text);
    }
}
