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
use Plib\CsrfProtector;
use Plib\FakeRequest;
use Plib\View;
use Realblog\Infra\DB;
use Realblog\Infra\Finder;
use ApprovalTests\Approvals;
use Realblog\Infra\FakeFileSystem;

class DataExchangeControllerTest extends TestCase
{
    /** @var CsrfProtector&MockObject */
    private $csrfProtector;

    public function setUp(): void
    {
        $this->csrfProtector = $this->createStub(CsrfProtector::class);
        $this->csrfProtector->method("token")->willReturn("e3c1b42a6098b48a39f9f54ddb3388f7");
    }

    public function testRendersOverview()
    {
        $sut = new DataExchangeController(
            "./plugins/realblog/",
            "./content/",
            $this->db(true),
            $this->finder(),
            $this->csrfProtector,
            new FakeFileSystem(),
            $this->view()
        );
        $request = new FakeRequest();
        $response = $sut($request);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersOverviewWithCsvFile()
    {
        $sut = new DataExchangeController(
            "./plugins/realblog/",
            "./content/",
            $this->db(true),
            $this->finder(),
            $this->csrfProtector,
            new FakeFileSystem(["isReadable" => true, "fileMTime" => 1677251242]),
            $this->view()
        );
        $request = new FakeRequest();
        $response = $sut($request);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersExportConfirmation(): void
    {
        $sut = new DataExchangeController(
            "./plugins/realblog/",
            "./content/",
            $this->db(true),
            $this->finder(),
            $this->csrfProtector,
            new FakeFileSystem(["fileExists" => true,]),
            $this->view()
        );
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=export",
        ]);
        $response = $sut($request);
        $this->assertEquals("Realblog – Export to CSV", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testExportIsCsrfProtected()
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $sut = new DataExchangeController(
            "./plugins/realblog/",
            "./content/",
            $this->db(true),
            $this->finder(),
            $this->csrfProtector,
            new FakeFileSystem(),
            $this->view()
        );
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=export",
            "post" => ["realblog_do" => ""]],
        );
        $response = $sut($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testSuccessfulExportRedirects()
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $sut = new DataExchangeController(
            "./plugins/realblog/",
            "./content/",
            $this->db(true),
            $this->finder(),
            $this->csrfProtector,
            new FakeFileSystem(),
            $this->view()
        );
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=export",
            "post" => ["realblog_do" => ""],
        ]);
        $response = $sut($request);
        $this->assertEquals("http://example.com/?realblog&admin=data_exchange", $response->location());
    }

    public function testExportReportsFailure()
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $sut = new DataExchangeController(
            "./plugins/realblog/",
            "./content/",
            $this->db(false),
            $this->finder(),
            $this->csrfProtector,
            new FakeFileSystem(),
            $this->view()
        );
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=export",
            "post" => ["realblog_do" => ""],
        ]);
        $response = $sut($request);
        $this->assertEquals("Realblog – Export to CSV", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testRendersImportConfirmation(): void
    {
        $sut = new DataExchangeController(
            "./plugins/realblog/",
            "./content/",
            $this->db(true),
            $this->finder(),
            $this->csrfProtector,
            new FakeFileSystem(["isReadable" => true, "fileMTime" => 1677251242]),
            $this->view()
        );
        $request = new FakeRequest(["url" => "http://example.com/?&action=import"]);
        $response = $sut($request);
        $this->assertEquals("Realblog – Import from CSV", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testImportRedirectsIfCsvFileIsMissing(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $sut = new DataExchangeController(
            "./plugins/realblog/",
            "./content/",
            $this->db(true),
            $this->finder(),
            $this->csrfProtector,
            new FakeFileSystem(["isReadable" => false]),
            $this->view()
        );
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=import",
            "post" => ["realblog_do" => ""],
        ]);
        $response = $sut($request);
        $this->assertEquals("http://example.com/?realblog&admin=data_exchange", $response->location());

    }

    public function testImportIsCsrfProtected()
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $sut = new DataExchangeController(
            "./plugins/realblog/",
            "./content/",
            $this->db(true),
            $this->finder(),
            $this->csrfProtector,
            new FakeFileSystem(),
            $this->view()
        );
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=import",
            "post" => ["realblog_do" => ""],
        ]);
        $response = $sut($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testSuccessfulImportRedirects()
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $sut = new DataExchangeController(
            "./plugins/realblog/",
            "./content/",
            $this->db(true),
            $this->finder(),
            $this->csrfProtector,
            new FakeFileSystem(),
            $this->view()
        );
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=import",
            "post" => ["realblog_do" => ""],
        ]);
        $response = $sut($request);
        $this->assertEquals("http://example.com/?realblog&admin=data_exchange", $response->location());
    }

    public function testImportReportsFailure()
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $sut = new DataExchangeController(
            "./plugins/realblog/",
            "./content/",
            $this->db(false),
            $this->finder(),
            $this->csrfProtector,
            new FakeFileSystem(),
            $this->view()
        );
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=import",
            "post" => ["realblog_do" => ""],
        ]);
        $response = $sut($request);
        $this->assertEquals("Realblog – Import from CSV", $response->title());
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

    private function view()
    {
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $text = $plugin_tx['realblog'];
        return new View("./views/", $text);
    }
}
