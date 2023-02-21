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

namespace Realblog\Infra;

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

    /** @var View */
    private $view;

    /**
     * @param int $itemCount
     * @param int $page
     * @param int $pageCount
     * @param string $url
     */
    public function __construct($itemCount, $page, $pageCount, $url, View $view)
    {
        $this->itemCount = $itemCount;
        $this->page = $page;
        $this->pageCount = $pageCount;
        $this->url = $url;
        $this->view = $view;
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
        $pages = [];
        foreach ($this->gatherPages() as $page) {
            $pages[] = $page !== null ? ["num" => $page, "url" => sprintf($url, $page)] : null;
        }
        $data = [
            'itemCount' => $this->itemCount,
            'currentPage' => $this->page,
            'pages' => $pages,
        ];
        return $this->view->render('pagination', $data);
    }

    /**
     * @return list<int|null>
     */
    private function gatherPages()
    {
        global $plugin_cf;

        $radius = (int) $plugin_cf['realblog']['pagination_radius'];
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
