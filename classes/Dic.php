<?php

/**
 * Copyright 2006-2010 Jan Kanters
 * Copyright 2010-2014 Gert Ebersbach
 * Copyright 2014-2023 Christoph M. Becker
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

use Plib\SystemChecker;
use Realblog\Infra\CsrfProtector;
use Realblog\Infra\DB;
use Realblog\Infra\Editor;
use Realblog\Infra\FileSystem;
use Realblog\Infra\Finder;
use Realblog\Infra\Pages;
use Realblog\Infra\View;

class Dic
{
    public static function makeGeneralController(): GeneralController
    {
        return new GeneralController(self::makeConf(), self::makeDb(), self::makeView());
    }

    public static function makeBlogController(): BlogController
    {
        return new BlogController(
            self::makeConf(),
            self::makeDb(),
            new Finder(self::makeDb()),
            self::makeView(),
            new Pages
        );
    }

    public static function makeLinkController(): LinkController
    {
        return new LinkController(
            self::makeConf(),
            new Pages,
            new Finder(Dic::makeDb()),
            self::makeView()
        );
    }

    public static function makeFeedLinkController(): FeedLinkController
    {
        global $pth;
        return new FeedLinkController($pth["folder"]["plugins"] . "realblog/", self::makeView());
    }

    public static function makeMostPopularController(): MostPopularController
    {
        return new MostPopularController(
            self::makeConf(),
            new Pages,
            new Finder(Dic::makeDb()),
            self::makeView()
        );
    }

    public static function makeFeedController(): FeedController
    {
        global $pth;
        return new FeedController(
            $pth["folder"]["images"],
            self::makeConf(),
            new Finder(Dic::makeDb()),
            new Pages,
            self::makeView()
        );
    }

    public static function makeInfoController(): InfoController
    {
        global $pth;
        return new InfoController(
            $pth["folder"]["plugins"] . "realblog/",
            self::makeConf(),
            new SystemChecker(),
            self::makeView()
        );
    }

    public static function makeMainAdminController(): MainAdminController
    {
        global $pth;
        return new MainAdminController(
            $pth["folder"]["plugins"] . "realblog/",
            self::makeConf(),
            Dic::makeDb(),
            new Finder(Dic::makeDb()),
            new CsrfProtector,
            self::makeView(),
            new Editor()
        );
    }

    public static function makeDataExchangeController(): DataExchangeController
    {
        global $pth;
        return new DataExchangeController(
            $pth["folder"]["plugins"] . "realblog/",
            $pth["folder"]["content"],
            Dic::makeDb(),
            new Finder(Dic::makeDb()),
            new CsrfProtector,
            new FileSystem,
            self::makeView()
        );
    }

    private static function makeDb(): DB
    {
        global $pth;
        static $instance = null;

        if ($instance === null) {
            $instance = new DB($pth['folder']['content'] . "realblog/realblog.db");
        }
        return $instance;
    }

    private static function makeView(): View
    {
        global $pth, $plugin_tx;

        return new View("{$pth['folder']['plugins']}realblog/views/", $plugin_tx['realblog']);
    }

    /** @return array<string,string> */
    private static function makeConf(): array
    {
        global $plugin_cf, $plugin_tx;

        return ["rss_page" => $plugin_tx["realblog"]["rss_page"]] + $plugin_cf["realblog"];
    }
}
