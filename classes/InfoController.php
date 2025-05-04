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
use Realblog\Infra\Request;
use Realblog\Infra\View;
use Realblog\Value\Response;

class InfoController
{
    /** @var string */
    private $pluginFolder;

    /** @var array<string,string> */
    private $conf;

    /** @var SystemChecker */
    private $systemChecker;

    /** @var View */
    private $view;

    /** @param array<string,string> $conf */
    public function __construct(string $pluginFolder, array $conf, SystemChecker $systemChecker, View $view)
    {
        $this->pluginFolder = $pluginFolder;
        $this->conf = $conf;
        $this->systemChecker = $systemChecker;
        $this->view = $view;
    }

    public function __invoke(Request $request): Response
    {
        $checks = [];
        foreach ($this->getChecks() as [$key, $arg, $state]) {
            $checks[] = [
                "key" => $key,
                "arg" => $arg,
                "class" => "xh_$state",
                "state" => "syscheck_$state",
            ];
        }
        return Response::create($this->view->render("info", [
            "version" => REALBLOG_VERSION,
            "heading" => $this->conf["heading_level"],
            "checks" => $checks,
        ]));
    }

    /** @return list<array{string,string,string}> */
    public function getChecks(): array
    {
        $checks = [];
        $phpVersion = "7.1.0";
        $checks[] = [
            "syscheck_phpversion",
            $phpVersion,
            $this->systemChecker->checkVersion(PHP_VERSION, $phpVersion) ? "success" : "fail"
        ];
        foreach (array("sqlite3") as $extension) {
            $checks[] = [
                "syscheck_extension",
                $extension,
                $this->systemChecker->checkExtension($extension) ? "success" : "fail",
            ];
        }
        $xhVersion = "1.7.0";
        $checks[] = [
            "syscheck_xhversion",
            $xhVersion,
            $this->systemChecker->checkVersion(CMSIMPLE_XH_VERSION, "CMSimple_XH $xhVersion") ? "success" : "fail",
        ];
        $folders = array(
            $this->pluginFolder . "config",
            $this->pluginFolder ."css",
            $this->pluginFolder . "languages",
        );
        foreach ($folders as $folder) {
            $checks[] = [
                "syscheck_writable",
                $folder,
                $this->systemChecker->checkWritability($folder) ? "success" : "warning",
            ];
        }
        return $checks;
    }
}
