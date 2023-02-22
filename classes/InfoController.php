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

use Realblog\Infra\Request;
use Realblog\Infra\SystemChecker;
use Realblog\Infra\View;

class InfoController
{
    /** @var array<string,string> */
    private $conf;

    /** @var array<string,string> */
    private $text;

    /** @var SystemChecker */
    private $systemChecker;

    /** @var View */
    private $view;

    /**
     * @param array<string,string> $conf
     * @param array<string,string> $text
     */
    public function __construct(array $conf, array $text, SystemChecker $systemChecker, View $view)
    {
        $this->conf = $conf;
        $this->text = $text;
        $this->systemChecker = $systemChecker;
        $this->view = $view;
    }

    public function __invoke(Request $request): string
    {
        $checks = [];
        foreach ($this->getChecks($request->pluginsFolder()) as $label => $state) {
            $checks[] = [
                "label" => $label,
                "state" => $state,
                "state_label" => $this->text["syscheck_$state"],
            ];
        }
        return $this->view->render("info", [
            "version" => Plugin::VERSION,
            "heading" => $this->conf["heading_level"],
            "checks" => $checks,
        ]);
    }

    /** @return array<string,string> */
    public function getChecks(string $pluginsFolder): array
    {
        $checks = array();
        $phpVersion = "7.1.0";
        $checks[sprintf($this->text["syscheck_phpversion"], $phpVersion)] =
             $this->systemChecker->checkPHPVersion($phpVersion) ? "success" : "fail";
        foreach (array("sqlite3") as $extension) {
            $checks[sprintf($this->text["syscheck_extension"], $extension)] =
                $this->systemChecker->checkExtension($extension) ? "success" : "fail";
        }
        $xhVersion = "1.7.0";
        $checks[sprintf($this->text["syscheck_xhversion"], $xhVersion)] =
            $this->systemChecker->checkXHVersion($xhVersion) ? "success" : "fail";
        $folders = array(
            $pluginsFolder . "realblog/config",
            $pluginsFolder ."realblog/css",
            $pluginsFolder . "realblog/languages",
        );
        foreach ($folders as $folder) {
            $checks[sprintf($this->text["syscheck_writable"], $folder)] =
                $this->systemChecker->checkWritability($folder) ? "success" : "warning";
        }
        return $checks;
    }
}
