<?php

/**
 * Copyright 2021 Christoph M. Becker
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

namespace Realblog\Value;

class Article
{
    /**
     * @var int
     * @readonly
     */
    public $id;

    /**
     * @var int
     * @readonly
     */
    public $date;

    /**
     * @var int
     * @readonly
     */
    public $status;

    /**
     * @var string
     * @readonly
     */
    public $categories;

    /**
     * @var string
     * @readonly
     */
    public $title;

    /**
     * @var string
     * @readonly
     */
    public $teaser;

    /**
     * @var bool
     * @readonly
     */
    public $hasBody;

    /**
     * @var bool
     * @readonly
     */
    public $feedable;

    /**
     * @var bool
     * @readonly
     */
    public $commentable;

    /**
     * @param int $id
     * @param int $date
     * @param int $status
     * @param string $categories
     * @param string $title
     * @param string $teaser
     * @param bool $hasBody
     * @param bool $feedable
     * @param bool $commentable
     */
    public function __construct($id, $date, $status, $categories, $title, $teaser, $hasBody, $feedable, $commentable)
    {
        $this->id = $id;
        $this->date = $date;
        $this->status = $status;
        $this->categories = $categories;
        $this->title = $title;
        $this->teaser = $teaser;
        $this->hasBody = $hasBody;
        $this->feedable = $feedable;
        $this->commentable = $commentable;
    }
}
