<?php

/**
 * Copyright 2006-2010 Jan Kanters
 * Copyright 2010-2014 Gert Ebersbach
 * Copyright 2014-2017 Christoph M. Becker
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

class Pagination
{
    /**
     * @var int
     */
    private $itemCount;

    /**
     * @var int
     */
    private $page;

    /**
     * @var int
     */
    private $pageCount;

    /**
     * @var string
     */
    private $url;

    /**
     * @param int $itemCount
     * @param int $page
     * @param int $pageCount
     * @param string $url
     */
    public function __construct($itemCount, $page, $pageCount, $url)
    {
        $this->itemCount = $itemCount;
        $this->page = $page;
        $this->pageCount = $pageCount;
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function render()
    {
        if ($this->pageCount <= 1) {
            return '';
        }
        $url = $this->url;
        $data = [
            'itemCount' => $this->itemCount,
            'currentPage' => $this->page,
            'pages' => $this->gatherPages(),
            'url' =>
            /**
             * @param int $page
             * @return string
             */
            function ($page) use ($url) {
                return sprintf($url, $page);
            },
        ];
        return (new View('pagination'))->render($data);
    }

    /**
     * @return ?int[]
     */
    private function gatherPages()
    {
        global $plugin_cf;

        $radius = $plugin_cf['realblog']['pagination_radius'];
        $pages = array(1);
        if ($this->page - $radius > 1 + 1) {
            $pages[] = null;
        }
        for ($i = $this->page - $radius; $i <= $this->page + $radius; $i++) {
            if ($i > 1 && $i < $this->pageCount) {
                $pages[] = $i;
            }
        }
        if ($this->page + $radius < $this->pageCount - 1) {
            $pages[] = null;
        }
        $pages[] = $this->pageCount;
        return $pages;
    }
}
