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
use Realblog\Infra\Request;
use Realblog\Infra\Response;

class GeneralController
{
    /** @var array<string,string> */
    private $conf;

    /** @var DB */
    private $db;

    /** @param array<string,string> $conf */
    public function __construct(array $conf, DB $db)
    {
        $this->conf = $conf;
        $this->db = $db;
    }

    public function __invoke(Request $request): Response
    {
        $response = new Response;
        if ($this->conf['auto_publish']) {
            $this->db->autoChangeStatus('publishing_date', 1);
        }
        if ($this->conf['auto_archive']) {
            $this->db->autoChangeStatus('archiving_date', 2);
        }
        if ($this->conf['rss_enabled']) {
            return $response->setHjs(
                "\n<link rel=\"alternate\" type=\"application/rss+xml\" href=\"./?realblog_feed=rss\">"
            );
        }
        return $response;
    }
}
