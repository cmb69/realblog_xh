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

use Realblog\Infra\DB;
use Realblog\Infra\Editor;
use Realblog\Infra\Finder;
use Realblog\Infra\ScriptEvaluator;
use Realblog\Infra\SystemChecker;
use Realblog\Infra\View;

class Dic
{
    public static function makeBlogController(): BlogController
    {
        return new BlogController(
            self::makeConf(),
            self::makeDb(),
            new Finder(self::makeDb()),
            self::makeView(),
            new ScriptEvaluator
        );
    }

    public static function makeArchiveController(): ArchiveController
    {
        return new ArchiveController(
            self::makeConf(),
            self::makeDb(),
            new Finder(self::makeDb()),
            self::makeView(),
            new ScriptEvaluator
        );
    }

    public static function makeLinkController(): LinkController
    {
        global $u;

        return new LinkController(
            self::makeConf(),
            $u,
            new Finder(Dic::makeDb()),
            self::makeView(),
            new ScriptEvaluator()
        );
    }

    public static function makeFeedLinkController(): FeedLinkController
    {
        return new FeedLinkController(self::makeView());
    }

    public static function makeMostPopularController(): MostPopularController
    {
        global $u;

        return new MostPopularController(
            self::makeConf(),
            $u,
            new Finder(Dic::makeDb()),
            self::makeView()
        );
    }

    public static function makeFeedController(): FeedController
    {
        return new FeedController(
            self::makeConf(),
            new Finder(Dic::makeDb()),
            new ScriptEvaluator(),
            self::makeView()
        );
    }

    public static function makeInfoController(): InfoController
    {
        global $plugin_tx;

        return new InfoController(
            self::makeConf(),
            $plugin_tx["realblog"],
            new SystemChecker,
            self::makeView()
        );
    }

    public static function makeMainAdminController(): MainAdminController
    {
        global $_XH_csrfProtection;

        return new MainAdminController(
            self::makeConf(),
            Dic::makeDb(),
            new Finder(Dic::makeDb()),
            $_XH_csrfProtection,
            self::makeView(),
            new Editor()
        );
    }

    public static function makeDataExchangeController(): DataExchangeController
    {
        global $_XH_csrfProtection;

        return new DataExchangeController(
            Dic::makeDb(),
            new Finder(Dic::makeDb()),
            $_XH_csrfProtection,
            self::makeView()
        );
    }

    public static function makeDb(): DB
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
