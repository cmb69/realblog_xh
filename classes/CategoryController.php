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

use Plib\Request;
use Plib\Response;
use Plib\View;
use Realblog\Infra\DB;
use Realblog\Infra\Finder;

class CategoryController
{
    /** @var array<string,string> */
    private $conf;

    /** @var Finder */
    private $finder;

    /** @var View */
    private $view;

    /** @param array<string,string> $conf */
    public function __construct(
        array $conf,
        Finder $finder,
        View $view
    ) {
        $this->conf = $conf;
        $this->finder = $finder;
        $this->view = $view;
    }

    public function __invoke(Request $request): Response
    {
        $categories = [];
        foreach ($this->finder->findAllCategories() as $category) {
            $url = $request->url()->page($this->conf["blog_page"])->with("realblog_category", $category);
            $categories[$category] = $url->relative();
        }
        return Response::create($this->view->render("categories", [
            "categories" => $categories,
        ]));
    }
}
