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

namespace Realblog\Infra;

use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testRedirectRedirects(): void
    {
        $response = new FakeResponse;
        $response->redirect((new Url)->withPath("somewhere-else"));
        $response->fire();
        $this->assertEquals(["Location: http://example.com/somewhere-else"], $response->headers());
        $this->assertTrue($response->exited());
    }

    public function testContentTypeExits(): void
    {
        $response = new FakeResponse;
        $response->setContentType("text/plain");
        $response->fire();
        $this->assertTrue($response->exited());
    }

    public function testSetsTitle(): void
    {
        global $title;

        $response = new FakeResponse;
        $response->setTitle("A Title");
        $response->fire();
        $this->assertEquals("A Title", $title);
    }

    public function testSetsDescription(): void
    {
        global $description;

        $response = new FakeResponse;
        $response->setDescription("A Description");
        $response->fire();
        $this->assertEquals("A Description", $description);
    }

    public function testSetsHjs(): void
    {
        global $hjs;

        $response = new FakeResponse;
        $response->setHjs("<meta></meta>");
        $response->fire();
        $this->assertEquals("<meta></meta>", $hjs);
    }

    public function testSetsBjs(): void
    {
        global $bjs;

        $response = new FakeResponse;
        $response->setBjs("<script></script>");
        $response->fire();
        $this->assertEquals("<script></script>", $bjs);
    }

    public function testSetsCookies(): void
    {
        $response = new FakeResponse;
        $response->addCookie("foo", "bar");
        $response->fire();
        $this->assertEquals(["foo" => "bar"], $response->cookies());
    }
}
