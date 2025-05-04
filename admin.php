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
use Realblog\Infra\Responder;

/**
 * @var array<string,array<string,string>> $plugin_tx
 * @var string $admin
 * @var string $action
 * @var string $o
 */

$temp = [
    "heading" => $plugin_tx["realblog"]["exchange_heading"],
    "url" => Request::current()->url()->withPage("realblog")->with("admin", "data_exchange")->relative(),
];

XH_registerStandardPluginMenuItems(true);
XH_registerPluginMenuItem("realblog", $temp["heading"], $temp["url"]);

if (XH_wantsPluginAdministration("realblog")) {
    $o .= print_plugin_admin("on");
    pluginMenu("ROW");
    pluginMenu("TAB", XH_hsc($temp["url"]), "", $temp["heading"]);
    $o .= pluginMenu("SHOW");
    switch ($admin) {
        case "":
            $o .= Dic::makeInfoController()(Request::current())();
            break;
        case "plugin_main":
            $o .= Responder::respond(Dic::makeMainAdminController()(Request::current()));
            break;
        case "data_exchange":
            $o .= Dic::makeDataExchangeController()(Request::current())();
            break;
        default:
            $o .= plugin_admin_common();
    }
}

$temp = null;
