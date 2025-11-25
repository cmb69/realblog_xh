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

class FullArticle
{
    /** @var int */
    public $id;

    /** @var int */
    public $version;

    /** @var int */
    public $date;

    /** @var string */
    public $categories;

    /** @var string */
    public $title;

    /** @var string */
    public $teaser;

    /** @var string */
    public $body;

    /** @var bool */
    public $commentable;

    public static function fromStrings(
        string $id,
        string $version,
        string $date,
        string $categories,
        string $title,
        string $teaser,
        string $body,
        string $commentable
    ): self {
        return new self(
            (int) $id,
            (int) $version,
            strtotime($date) ?: 0,
            "," . $categories . ",",
            $title,
            $teaser,
            $body,
            (bool) $commentable
        );
    }

    public function __construct(
        int $id,
        int $version,
        int $date,
        string $categories,
        string $title,
        string $teaser,
        string $body,
        bool $commentable
    ) {
        $this->id = $id;
        $this->version = $version;
        $this->date = $date;
        $this->categories = $categories;
        $this->title = $title;
        $this->teaser = $teaser;
        $this->body = $body;
        $this->commentable = $commentable;
    }
}
